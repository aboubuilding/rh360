<?php

namespace App\Domain\Performance\Listeners;

use App\Domain\Performance\Events\EntretienValide;

class TracerValidationEntretien
{
    public function handle(EntretienValide $event): void
    {
        app(\App\Domain\Administration\Services\ServiceAudit::class)->tracer(
            'performance.entretien.valide',
            $event->entretien,
            [
                'salarie' => $event->entretien->salarie?->nom_complet,
                'note_finale' => $event->entretien->note_finale,
            ]
        );
    }
}