<?php

namespace App\Domain\Paie\Listeners;

use App\Domain\Paie\Events\PeriodeValidee;

class TracerValidationPeriode
{
    public function handle(PeriodeValidee $event): void
    {
        app(\App\Domain\Administration\Services\ServiceAudit::class)->tracer(
            'paie.periode.validee',
            $event->periode,
            [
                'libelle' => $event->periode->libelle,
                'bulletins' => $event->periode->bulletins()->count(),
            ]
        );
    }
}