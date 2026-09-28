<?php

namespace App\Domain\Carriere\Listeners;

use App\Domain\Carriere\Events\MouvementValide;
use App\Domain\Carriere\Models\MouvementCarriere;
use App\Domain\Carriere\Services\CircuitMouvement;

class PreparerProgrammation
{
    public function __construct(private CircuitMouvement $circuit) {}

    public function handle(MouvementValide $event): void
    {
        // Si une date d'effet est déjà renseignée et future,
        // passer automatiquement en « programmé »
        $mouvement = $event->mouvement;

        if ($mouvement->date_effet && $mouvement->date_effet->isFuture()) {
            $this->circuit->transitionner(
                $mouvement,
                \App\Domain\Carriere\Enums\StatutMouvement::PROGRAMME,
                'programmation_automatique',
            );
        }
    }
}