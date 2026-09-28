<?php

namespace App\Domain\Formation\Actions;

use App\Domain\Formation\Enums\StatutPresenceParticipant;
use App\Domain\Formation\Models\ParticipantFormation;

class EvaluerParticipantFormation
{
    public function executer(ParticipantFormation $participant, array $donnees): ParticipantFormation
    {
        $participant->update([
            'statut_presence' => $donnees['statut_presence'] ?? $participant->statut_presence->value,
            'score_avant' => $donnees['score_avant'] ?? $participant->score_avant,
            'score_apres' => $donnees['score_apres'] ?? $participant->score_apres,
            'note_satisfaction' => $donnees['note_satisfaction'] ?? $participant->note_satisfaction,
            'commentaire_evaluation' => $donnees['commentaire_evaluation'] ?? $participant->commentaire_evaluation,
            'reference_attestation' => $donnees['reference_attestation'] ?? $participant->reference_attestation,
        ]);

        return $participant->fresh();
    }
}