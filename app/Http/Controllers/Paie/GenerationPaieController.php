<?php

namespace App\Http\Controllers\Paie;

use App\Domain\Administration\Models\Entreprise;
use App\Domain\Paie\Actions\OuvrirPeriode;
use App\Domain\Paie\Models\BulletinPaie;
use App\Domain\Paie\Models\PeriodePaie;
use App\Domain\Paie\Services\MoteurPaie;
use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

/**
 * Paie du mois : choix de la période, génération des bulletins en un clic et téléchargement
 * des bulletins (un PDF regroupant tous les bulletins, ou une archive d'un PDF par salarié).
 */
class GenerationPaieController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', PeriodePaie::class);

        $periode = $request->filled('periode') ? PeriodePaie::with(['bulletins.salarie', 'anomalies.salarie'])->find($request->periode) : null;

        return view('paie.generation.index', [
            'periode' => $periode,
            'periodes' => PeriodePaie::withCount('bulletins')->orderByDesc('annee')->orderByDesc('mois')->limit(12)->get(),
            'annee' => (int) $request->get('annee', $periode?->annee ?? today()->year),
            'mois' => (int) $request->get('mois', $periode?->mois ?? today()->month),
        ]);
    }

    public function generer(Request $request, OuvrirPeriode $ouvrir, MoteurPaie $moteur)
    {
        $this->authorize('create', PeriodePaie::class);

        $donnees = $request->validate([
            'annee' => ['required', 'integer', 'min:2000', 'max:2100'],
            'mois' => ['required', 'integer', 'min:1', 'max:12'],
        ], [
            'annee.required' => 'L\'année est obligatoire.',
            'mois.required' => 'Le mois est obligatoire.',
        ]);

        $entrepriseId = auth()->user()->entreprise_id;
        $periode = PeriodePaie::where('annee', $donnees['annee'])->where('mois', $donnees['mois'])->first()
            ?? $ouvrir->executer($entrepriseId, $donnees['annee'], $donnees['mois']);
        if (! $periode->peutEtreCalculee()) {
            return $this->repondre($request, $periode, 'Cette période est validée : ses bulletins ne sont plus recalculés, ils restent téléchargeables.', 'error');
        }
        $this->authorize('calculer', $periode);

        try {
            $resultats = $moteur->calculer($periode);
        } catch (\DomainException $e) {
            return $this->repondre($request, $periode, $e->getMessage(), 'error');
        }

        $message = "{$resultats['bulletins_calcules']} bulletin(s) générés pour {$periode->libelle}.";
        if ($resultats['anomalies_bloquantes'] > 0) {
            $message .= " {$resultats['anomalies_bloquantes']} anomalie(s) bloquante(s) à corriger avant la validation.";
        }

        return $this->repondre($request, $periode, $message);
    }

    // Tous les bulletins de la période, dans un seul PDF (un bulletin par page) ou une archive ZIP.
    public function telecharger(Request $request, PeriodePaie $periode)
    {
        $this->authorize('view', $periode);

        $bulletins = BulletinPaie::where('periode_id', $periode->id)
            ->with(['salarie', 'periode', 'lignes.rubrique'])
            ->get()
            ->sortBy(fn ($b) => $b->salarie?->nom_complet)
            ->values();

        if ($bulletins->isEmpty()) {
            return back()->with('error', 'Aucun bulletin à télécharger pour cette période : générez d\'abord la paie.');
        }

        $entreprise = Entreprise::find($periode->entreprise_id);
        $nom = sprintf('bulletins-%04d-%02d', $periode->annee, $periode->mois);

        if ($request->get('format') === 'zip') {
            $chemin = tempnam(sys_get_temp_dir(), 'bul');
            $zip = new \ZipArchive();
            $zip->open($chemin, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
            foreach ($bulletins as $bulletin) {
                $pdf = Pdf::loadView('paie.bulletins.pdf', ['bulletin' => $bulletin, 'entreprise' => $entreprise])->output();
                $zip->addFromString("bulletin-{$bulletin->salarie?->matricule}-{$periode->code}.pdf", $pdf);
            }
            $zip->close();

            return response()->download($chemin, "{$nom}.zip", ['Content-Type' => 'application/zip'])->deleteFileAfterSend();
        }

        return Pdf::loadView('paie.bulletins.lot', [
            'bulletins' => $bulletins,
            'periode' => $periode,
            'entreprise' => $entreprise,
        ])->download("{$nom}.pdf");
    }

    private function repondre(Request $request, PeriodePaie $periode, string $message, string $type = 'success')
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'periode_id' => $periode->id], $type === 'success' ? 200 : 422);
        }

        return redirect()->route('paie.generation.index', ['periode' => $periode->id])->with($type, $message);
    }
}
