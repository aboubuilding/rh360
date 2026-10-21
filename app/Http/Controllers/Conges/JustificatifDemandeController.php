<?php

namespace App\Http\Controllers\Conges;

use App\Domain\Conges\Actions\RegulariserAbsenceParDemande;
use App\Domain\Conges\Models\Absence;
use App\Domain\Conges\Models\DemandeConge;
use App\Domain\Conges\Models\PieceDemande;
use App\Domain\Conges\Services\GestionJustificatifs;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Justificatifs des demandes, requalification, prolongation du délai pour force majeure et
 * régularisation d'une absence constatée sans demande.
 */
class JustificatifDemandeController extends Controller
{
    public function __construct(private GestionJustificatifs $justificatifs) {}

    public function deposer(Request $request, DemandeConge $demande)
    {
        $this->authorize('permission', 'conges.manage');

        $donnees = $request->validate([
            'nature' => ['required', 'string', 'max:160'],
            'fichier' => ['required', 'file', 'mimes:pdf,png,jpg,jpeg', 'max:8192'],
            'conforme' => ['nullable', 'boolean'],
        ], [
            'nature.required' => 'Précisez la nature du justificatif.',
            'fichier.required' => 'Le fichier est obligatoire.',
            'fichier.mimes' => 'Le fichier doit être un PDF ou une image.',
            'fichier.max' => 'Le fichier ne doit pas dépasser 8 Mo.',
        ]);

        // Contrôle immédiat seulement si l'utilisateur peut constater la conformité.
        $conforme = $request->boolean('conforme') && $request->user()->peut('conges.validate') ? true : null;

        return $this->executer($request, fn () => $this->justificatifs->deposer($demande, $request->file('fichier'), $donnees['nature'], $conforme), 'Justificatif enregistré.');
    }

    public function controler(Request $request, DemandeConge $demande, PieceDemande $piece)
    {
        $this->authorize('permission', 'conges.validate');
        abort_unless($piece->demande_id === $demande->id, 404);

        $donnees = $request->validate([
            'conforme' => ['required', 'boolean'],
            'motif_non_conforme' => ['nullable', 'required_if:conforme,0', 'string', 'max:500'],
        ], [
            'motif_non_conforme.required_if' => 'Précisez pourquoi le justificatif n\'est pas conforme.',
        ]);

        return $this->executer(
            $request,
            fn () => $this->justificatifs->controler($piece, (bool) $donnees['conforme'], $donnees['motif_non_conforme'] ?? null),
            $donnees['conforme'] ? 'Justificatif reconnu conforme.' : 'Justificatif déclaré non conforme.',
        );
    }

    public function telecharger(DemandeConge $demande, PieceDemande $piece)
    {
        $this->authorize('view', $demande);
        abort_unless($piece->demande_id === $demande->id && Storage::disk('local')->exists($piece->chemin), 404);

        return Storage::disk('local')->download($piece->chemin, $piece->nom_original);
    }

    public function requalifier(Request $request, DemandeConge $demande)
    {
        $this->authorize('permission', 'conges.validate');

        $motif = $request->validate(['motif' => ['required', 'string', 'max:2000']], ['motif.required' => 'Le motif est obligatoire.'])['motif'];

        return $this->executer($request, fn () => $this->justificatifs->requalifier($demande, $motif), 'Demande requalifiée.');
    }

    public function prolonger(Request $request, DemandeConge $demande)
    {
        $this->authorize('permission', 'conges.validate');

        $donnees = $request->validate([
            'echeance' => ['required', 'date'],
            'motif' => ['required', 'string', 'max:2000'],
        ], [
            'echeance.required' => 'La nouvelle échéance est obligatoire.',
            'motif.required' => 'Précisez le cas de force majeure.',
        ]);

        return $this->executer(
            $request,
            fn () => $this->justificatifs->prolongerEcheance($demande, Carbon::parse($donnees['echeance']), $donnees['motif']),
            'Délai du justificatif prolongé.',
        );
    }

    public function regulariserAbsence(Request $request, Absence $absence, RegulariserAbsenceParDemande $action)
    {
        $this->authorize('permission', 'conges.manage');

        $donnees = $request->validate([
            'type_conge_id' => ['required', Rule::exists('types_conges', 'id')->where('entreprise_id', $absence->entreprise_id)],
            'evenement_permission_id' => ['nullable', Rule::exists('evenements_permission', 'id')->where('type_conge_id', $request->input('type_conge_id'))],
            'date_evenement' => ['nullable', 'date', 'required_with:evenement_permission_id'],
            'motif_force_majeure' => ['required', 'string', 'max:2000'],
        ], [
            'type_conge_id.required' => 'Le type de demande est obligatoire.',
            'date_evenement.required_with' => 'La date de l\'événement est obligatoire.',
            'motif_force_majeure.required' => 'Précisez le cas de force majeure.',
        ]);

        try {
            $demande = $action->executer($absence, $donnees);
        } catch (\DomainException $e) {
            return $this->repondre($request, $e->getMessage(), 422);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Demande de régularisation créée.', 'redirect' => route('conges.demandes.show', $demande)]);
        }

        return redirect()->route('conges.demandes.show', $demande)->with('success', 'Demande de régularisation créée.');
    }

    private function executer(Request $request, callable $operation, string $message)
    {
        try {
            $operation();
        } catch (\DomainException $e) {
            return $this->repondre($request, $e->getMessage(), 422);
        }

        return $this->repondre($request, $message);
    }

    private function repondre(Request $request, string $message, int $statut = 200)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], $statut);
        }

        return back()->with($statut === 200 ? 'success' : 'error', $message);
    }
}
