<?php

namespace App\Domain\Formation\Listeners;

use App\Domain\Formation\Events\BesoinFormationValide;

class TracerValidationBesoin
{
    public function handle(BesoinFormationValide $event): void
    {
        app(\App\Domain\Administration\Services\ServiceAudit::class)->tracer(
            'formation.besoin.valide',
            $event->besoin,
            [
                'intitule' => $event->besoin->intitule,
                'annee_cible' => $event->besoin->annee_cible,
            ]
        );
    }
}