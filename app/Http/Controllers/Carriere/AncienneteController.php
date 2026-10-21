<?php

namespace App\Http\Controllers\Carriere;

use App\Domain\Carriere\Enums\MotifDepart;
use App\Domain\Carriere\Models\DistinctionHonorifique;
use App\Domain\Carriere\Models\PeriodeEmploiAnterieure;
use App\Domain\Carriere\Services\GestionnaireDistinctions;
use App\Domain\Paie\Enums\StatutPeriode;
use App\Domain\Paie\Models\PeriodePaie;
use App\Domain\Personnel\Models\Salarie;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Réembauches (emplois antérieurs cumulés à l'ancienneté) et distinctions honorifiques.
 */
class AncienneteController extends Controller
{
    public function ajouterPeriode(Request $request, Salarie $salarie)
    {
        $this->authorize('permission', 'carriere.manage');

        $donnees = $request->validate([
            'date_debut' => ['required', 'date'],
            'date_fin' => ['required', 'date', 'after_or_equal:date_debut', 'before:'.$salarie->date_embauche?->toDateString()],
            'motif_depart' => ['required', Rule::enum(MotifDepart::class)],
            'piece' => ['nullable', 'file', 'mimes:pdf,png,jpg,jpeg', 'max:8192'],
        ], [
            'date_debut.required' => 'La date de début est obligatoire.',
            'date_fin.required' => 'La date de fin est obligatoire.',
            'date_fin.after_or_equal' => 'La date de fin doit être postérieure à la date de début.',
            'date_fin.before' => 'L\'emploi antérieur doit se terminer avant l\'embauche actuelle.',
            'motif_depart.required' => 'Le motif du départ est obligatoire.',
        ]);

        PeriodeEmploiAnterieure::create([
            'entreprise_id' => $salarie->entreprise_id,
            'salarie_id' => $salarie->id,
            'date_debut' => $donnees['date_debut'],
            'date_fin' => $donnees['date_fin'],
            'motif_depart' => $donnees['motif_depart'],
            'chemin_piece' => $request->file('piece')?->store("carriere/emplois-anterieurs/{$salarie->id}", 'local'),
            'cree_par' => auth()->id(),
        ]);

        return $this->repondre($request, 'Emploi antérieur enregistré.');
    }

    public function verserDistinction(Request $request, DistinctionHonorifique $distinction, GestionnaireDistinctions $gestionnaire)
    {
        $this->authorize('permission', 'carriere.manage');

        $periode = PeriodePaie::where('statut', StatutPeriode::OUVERTE->value)->orderByDesc('annee')->orderByDesc('mois')->first();
        if (! $periode) {
            return $this->repondre($request, 'Aucune période de paie ouverte.', 422);
        }

        try {
            $gestionnaire->verser($distinction, $periode);
        } catch (\DomainException $e) {
            return $this->repondre($request, $e->getMessage(), 422);
        }

        return $this->repondre($request, "Gratification portée sur la paie de {$periode->mois}/{$periode->annee}.");
    }

    private function repondre(Request $request, string $message, int $statut = 200)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], $statut);
        }

        return back()->with($statut === 200 ? 'success' : 'error', $message);
    }
}
