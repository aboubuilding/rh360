<?php

namespace App\Domain\Performance\Actions;

use App\Domain\Performance\Enums\StatutEntretien;
use App\Domain\Performance\Models\EntretienEvaluation;
use App\Domain\Performance\Services\CalculateurNoteFinale;

class RealiserEntretienEvaluation
{
    public function __construct(private CalculateurNoteFinale $calculateur) {}

    public function executer(EntretienEvaluation $entretien, array $donnees): EntretienEvaluation
    {
        if ($entretien->statut === StatutEntretien::VALIDE) {
            throw new \DomainException('Cet entretien a déjà été validé.');
        }

        $entretien->update([
            'note_manager' => $donnees['note_manager'],
            'points_forts' => $donnees['points_forts'] ?? null,
            'besoins_developpement' => $donnees['besoins_developpement'] ?? null,
            'commentaire_manager' => $donnees['commentaire_manager'] ?? null,
            'action_amelioration' => $donnees['action_amelioration'] ?? null,
            'date_echeance_amelioration' => $donnees['date_echeance_amelioration'] ?? null,
            'date_entretien' => $donnees['date_entretien'] ?? now(),
            'statut' => StatutEntretien::REALISE->value,
        ]);

        // Recalculer la note finale
        $resultat = $this->calculateur->calculerAvecAppreciation($entretien->fresh());
        $entretien->update(['note_finale' => $resultat['note_finale']]);

        return $entretien->fresh();
    }

    public function valider(EntretienEvaluation $entretien): EntretienEvaluation
    {
        if ($entretien->statut !== StatutEntretien::REALISE) {
            throw new \DomainException('Seul un entretien réalisé peut être validé.');
        }

        $entretien->update(['statut' => StatutEntretien::VALIDE->value]);

        return $entretien->fresh();
    }
}