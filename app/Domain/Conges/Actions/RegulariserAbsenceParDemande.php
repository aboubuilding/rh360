<?php

namespace App\Domain\Conges\Actions;

use App\Domain\Conges\Models\Absence;
use App\Domain\Conges\Models\DemandeConge;
use Illuminate\Support\Facades\DB;

/**
 * Absence constatée sans demande préalable (force majeure) : une demande de régularisation est
 * créée pour les dates de l'absence et soumise aussitôt. Le délai de justification du type court
 * depuis l'événement ou le premier jour d'absence.
 */
class RegulariserAbsenceParDemande
{
    public function __construct(
        private CreerDemandeConge $creer,
        private SoumettreDemandeConge $soumettre,
    ) {}

    /**
     * @param  array{type_conge_id: int, motif_force_majeure: string, evenement_permission_id?: ?int, date_evenement?: ?string}  $donnees
     */
    public function executer(Absence $absence, array $donnees): DemandeConge
    {
        if (DemandeConge::where('absence_id', $absence->id)->whereNotIn('statut', ['cancelled'])->exists()) {
            throw new \DomainException('Cette absence fait déjà l\'objet d\'une demande de régularisation.');
        }

        return DB::transaction(function () use ($absence, $donnees) {
            $debut = $absence->debut_le->copy()->startOfDay();
            $fin = ($absence->fin_le ?? $absence->debut_le)->copy()->startOfDay();

            $demande = $this->creer->executer([
                'salarie_id' => $absence->salarie_id,
                'type_conge_id' => $donnees['type_conge_id'],
                'evenement_permission_id' => $donnees['evenement_permission_id'] ?? null,
                'date_evenement' => $donnees['date_evenement'] ?? null,
                'date_debut' => $debut->toDateString(),
                'date_reprise' => $fin->addDay()->toDateString(),
                'motif' => $absence->motif,
                'force_majeure' => true,
                'motif_force_majeure' => $donnees['motif_force_majeure'],
                'absence_id' => $absence->id,
            ]);

            return $this->soumettre->executer($demande);
        });
    }
}
