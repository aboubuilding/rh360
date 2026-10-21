<?php

namespace App\Http\Controllers\Paie;

use App\Domain\Administration\Models\Entreprise;
use App\Domain\Paie\Models\ModelePaie;
use App\Domain\Paie\Services\ApercuBulletinPaie;
use App\Domain\Personnel\Models\Salarie;
use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Aperçu d'un modèle de bulletin : bulletin type (salarié fictif au minimum de la catégorie),
 * ou bulletin d'un salarié réel calculé avec ce modèle, sans rien enregistrer.
 */
class ApercuModelePaieController extends Controller
{
    public function __construct(private ApercuBulletinPaie $apercu) {}

    public function show(Request $request, ModelePaie $modele)
    {
        $this->authorize('view', $modele);
        [$parametres, $resultat] = $this->calculer($request, $modele);

        return view('paie.modeles.apercu', $parametres + [
            'modele' => $modele->load('categorie', 'rubriquesActives.rubrique'),
            'anomalies' => $resultat['anomalies'],
            'salaries' => Salarie::where('actif', true)->orderBy('nom')->get(),
        ]);
    }

    // Le bulletin seul, au gabarit du bulletin de paie : affiché dans l'aperçu, ou téléchargé en PDF.
    public function document(Request $request, ModelePaie $modele)
    {
        $this->authorize('view', $modele);
        [$parametres, $resultat] = $this->calculer($request, $modele);

        $donnees = [
            'bulletin' => $resultat['bulletin'],
            'entreprise' => Entreprise::find($modele->entreprise_id),
            'apercu' => true,
        ];

        if ($request->get('format') === 'pdf') {
            return Pdf::loadView('paie.bulletins.pdf', $donnees)->download('apercu-'.str($modele->nom)->slug().'.pdf');
        }

        return view('paie.bulletins.pdf', $donnees);
    }

    /** @return array{0: array<string, mixed>, 1: array{bulletin: \App\Domain\Paie\Models\BulletinPaie, anomalies: array}} */
    private function calculer(Request $request, ModelePaie $modele): array
    {
        $donnees = $request->validate([
            'mois' => ['nullable', 'date_format:Y-m'],
            'salarie_id' => ['nullable', 'integer'],
            'anciennete' => ['nullable', 'integer', 'min:0', 'max:45'],
        ]);
        $mois = Carbon::createFromFormat('Y-m-d', ($donnees['mois'] ?? today()->format('Y-m')).'-01')->startOfDay();
        $anciennete = (int) ($donnees['anciennete'] ?? 5);
        $salarie = ! empty($donnees['salarie_id']) ? Salarie::find($donnees['salarie_id']) : null;

        $resultat = $salarie
            ? $this->apercu->pourSalarie($modele, $salarie, $mois)
            : $this->apercu->type($modele, $mois, $anciennete);

        return [[
            'mois' => $mois->format('Y-m'),
            'anciennete' => $anciennete,
            'salarieChoisi' => $salarie,
        ], $resultat];
    }
}
