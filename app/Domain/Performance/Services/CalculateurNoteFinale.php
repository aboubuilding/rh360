<?php

namespace App\Domain\Performance\Services;

use App\Domain\Performance\Models\EntretienEvaluation;

class CalculateurNoteFinale
{
    /**
     * Pondérations par défaut : auto-évaluation 20 %, manager 80 %.
     */
    private const PONDERATION_AUTO = 0.20;
    private const PONDERATION_MANAGER = 0.80;

    /**
     * Calcule la note finale d'un entretien selon la pondération standard.
     */
    public function calculer(EntretienEvaluation $entretien): ?float
    {
        $auto = $entretien->note_auto_evaluation;
        $manager = $entretien->note_manager;

        // Si une seule des deux notes est présente
        if ($auto === null && $manager === null) return null;
        if ($auto === null) return (float) $manager;
        if ($manager === null) return (float) $auto;

        return round(
            ((float) $auto * self::PONDERATION_AUTO)
            + ((float) $manager * self::PONDERATION_MANAGER),
            2
        );
    }

    /**
     * Détermine l'appréciation qualitative selon la note finale.
     */
    public function appreciation(?float $note): string
    {
        if ($note === null) return 'Non évalué';

        return match (true) {
            $note >= 18 => 'Excellent',
            $note >= 15 => 'Très bien',
            $note >= 12 => 'Bien',
            $note >= 10 => 'Satisfaisant',
            $note >= 7  => 'Insuffisant',
            default     => 'Très insuffisant',
        };
    }

    /**
     * Calcule la note finale et l'appréciation associée.
     */
    public function calculerAvecAppreciation(EntretienEvaluation $entretien): array
    {
        $note = $this->calculer($entretien);

        return [
            'note_finale' => $note,
            'appreciation' => $this->appreciation($note),
        ];
    }
}