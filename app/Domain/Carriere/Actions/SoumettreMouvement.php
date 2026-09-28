<?php

namespace App\Domain\Carriere\Actions;

use App\Domain\Carriere\Enums\StatutMouvement;
use App\Domain\Carriere\Models\MouvementCarriere;
use App\Domain\Carriere\Services\CircuitMouvement;

class SoumettreMouvement
{
    public function __construct(private CircuitMouvement $circuit) {}

    public function executer(MouvementCarriere $mouvement): MouvementCarriere
    {
        return $this->circuit->transitionner(
            $mouvement,
            StatutMouvement::PROPOSE,
            'soumission'
        );
    }
}