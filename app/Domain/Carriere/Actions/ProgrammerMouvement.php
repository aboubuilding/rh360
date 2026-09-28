<?php

namespace App\Domain\Carriere\Actions;

use App\Domain\Carriere\Enums\StatutMouvement;
use App\Domain\Carriere\Models\MouvementCarriere;
use App\Domain\Carriere\Services\CircuitMouvement;
use Carbon\Carbon;

class ProgrammerMouvement
{
    public function __construct(private CircuitMouvement $circuit) {}

    public function executer(MouvementCarriere $mouvement, string $dateEffet): MouvementCarriere
    {
        $date = Carbon::parse($dateEffet);
        if ($date->isPast()) {
            throw new \DomainException('La date d\'effet doit être future pour programmer un mouvement.');
        }

        $mouvement->update([
            'date_effet' => $date,
            'date_notification' => now(),
        ]);

        return $this->circuit->transitionner(
            $mouvement,
            StatutMouvement::PROGRAMME,
            'programmation',
            "Programmé pour le {$date->format('d/m/Y')}",
        );
    }
}