<?php

namespace App\Domain\Formation\Actions;

use App\Domain\Formation\Enums\StatutPresenceParticipant;
use App\Domain\Formation\Models\ParticipantFormation;
use App\Domain\Formation\Models\SessionFormation;
use Illuminate\Support\Facades\DB;

class AjouterParticipantFormation
{
    public function executer(SessionFormation $session, int $salarieId): ParticipantFormation
    {
        return DB::transaction(function () use ($session, $salarieId) {
            // Éviter les doublons
            $existant = ParticipantFormation::where('session_formation_id', $session->id)
                ->where('salarie_id', $salarieId)
                ->first();

            if ($existant) {
                throw new \DomainException('Ce salarié est déjà inscrit à cette session.');
            }

            return ParticipantFormation::create([
                'entreprise_id' => $session->entreprise_id,
                'session_formation_id' => $session->id,
                'salarie_id' => $salarieId,
                'statut_presence' => StatutPresenceParticipant::INSCRIT->value,
                'etat' => 1,
            ]);
        });
    }

    public function retirer(ParticipantFormation $participant): void
    {
        if ($participant->statut_presence === StatutPresenceParticipant::PRESENT) {
            throw new \DomainException('Impossible de retirer un participant déjà présent.');
        }

        $participant->delete();
    }
}