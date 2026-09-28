<?php

namespace App\Domain\Carriere\Listeners;

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Carriere\Events\MouvementApplique;

class NotifierMouvementApplique
{
    public function handle(MouvementApplique $event): void
    {
        $mouvement = $event->mouvement;

        // Notifier les RH et DRH de l'entreprise
        $utilisateurs = Utilisateur::query()
            ->where('entreprise_id', $mouvement->entreprise_id)
            ->whereIn('role', ['rh', 'drh'])
            ->where('actif', true)
            ->get();

        // Ici on pourrait créer des notifications. Pour l'instant on se
        // contente de tracer via le journal d'audit.
        app(\App\Domain\Administration\Services\ServiceAudit::class)->tracer(
            'carriere.mouvement.applique',
            $mouvement,
            [
                'numero' => $mouvement->numero_mouvement,
                'type' => $mouvement->type_mouvement->value,
                'date_effet' => $mouvement->date_effet?->format('Y-m-d'),
            ]
        );
    }
}