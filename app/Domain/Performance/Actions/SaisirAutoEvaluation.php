<?php

namespace App\Domain\Performance\Actions;

use App\Domain\Performance\Enums\StatutEntretien;
use App\Domain\Performance\Models\EntretienEvaluation;

class SaisirAutoEvaluation
{
    public function executer(EntretienEvaluation $entretien, float $note, ?string $commentaire = null): EntretienEvaluation
    {
        if (! in_array($entretien->statut, [StatutEntretien::A_PREPARER, StatutEntretien::AUTO_EVALUE], true)) {
            throw new \DomainException('L\'auto-évaluation n\'est plus possible à ce stade.');
        }

        $entretien->update([
            'note_auto_evaluation' => $note,
            'statut' => StatutEntretien::AUTO_EVALUE->value,
        ]);

        return $entretien->fresh();
    }
}