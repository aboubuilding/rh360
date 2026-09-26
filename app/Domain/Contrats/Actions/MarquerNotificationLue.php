<?php

namespace App\Domain\Contrats\Actions;

use App\Domain\Contrats\Models\AlerteContrat;
use App\Domain\Contrats\Models\NotificationContrat;

class MarquerNotificationLue
{
    public function executer(NotificationContrat $notification): void
    {
        $notification->marquerLu();
    }

    public function toutesLues(int $utilisateurId): int
    {
        return NotificationContrat::query()
            ->where('utilisateur_id', $utilisateurId)
            ->whereNull('lu_le')
            ->update(['lu_le' => now()]);
    }

    public function toutesLuesPourAlerte(AlerteContrat $alerte): int
    {
        return NotificationContrat::query()
            ->where('alerte_id', $alerte->id)
            ->whereNull('lu_le')
            ->update(['lu_le' => now()]);
    }
}