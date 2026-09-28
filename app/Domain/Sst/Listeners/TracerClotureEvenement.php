<?php

namespace App\Domain\Sst\Listeners;

use App\Domain\Sst\Events\EvenementSecuriteCloture;

class TracerClotureEvenement
{
    public function handle(EvenementSecuriteCloture $event): void
    {
        app(\App\Domain\Administration\Services\ServiceAudit::class)->tracer(
            'sst.evenement.cloture',
            $event->evenement,
            [
                'type' => $event->evenement->type_evenement?->value,
                'date_survenance' => $event->evenement->date_survenance?->format('Y-m-d'),
            ]
        );
    }
}