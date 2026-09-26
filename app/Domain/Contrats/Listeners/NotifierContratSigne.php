<?php

namespace App\Domain\Contrats\Listeners;

use App\Domain\Contrats\Events\ContratSigne;
use App\Domain\Contrats\Models\NotificationContrat;

class NotifierContratSigne
{
    public function handle(ContratSigne $event): void
    {
        $contrat = $event->contrat;

        // Notifier les rôles rh et drh de l'entreprise
        $utilisateurs = \App\Domain\Administration\Models\Utilisateur::query()
            ->where('entreprise_id', $contrat->entreprise_id)
            ->whereIn('role', ['rh', 'drh'])
            ->where('actif', true)
            ->get();

        foreach ($utilisateurs as $u) {
            // Créer une notification (utilise la table notifications_contrats)
            $alerte = $contrat->alertes()->where('cle', 'signature_attendue')->first();
            if ($alerte) {
                NotificationContrat::create([
                    'entreprise_id' => $contrat->entreprise_id,
                    'alerte_id' => $alerte->id,
                    'utilisateur_id' => $u->id,
                    'base_calcul' => $alerte->base_calcul->value,
                    'seuil' => 0,
                ]);
            }
        }
    }
}