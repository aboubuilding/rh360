<?php

namespace App\Domain\Carriere\Actions;

use App\Domain\Carriere\Enums\StatutMouvement;
use App\Domain\Carriere\Models\MouvementCarriere;
use App\Domain\Carriere\Services\CircuitMouvement;

class CloturerMouvement
{
    public function __construct(private CircuitMouvement $circuit) {}

    public function executer(MouvementCarriere $mouvement, ?string $motif = null): MouvementCarriere
    {
        return $this->circuit->transitionner(
            $mouvement,
            StatutMouvement::TERMINE,
            'cloture',
            $motif,
        );
    }
}