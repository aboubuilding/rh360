<?php

namespace App\Domain\Carriere\Actions;

use App\Domain\Carriere\Enums\StatutMouvement;
use App\Domain\Carriere\Models\MouvementCarriere;
use App\Domain\Carriere\Services\CircuitMouvement;

class AnnulerMouvement
{
    public function __construct(private CircuitMouvement $circuit) {}

    public function executer(MouvementCarriere $mouvement, string $motif): MouvementCarriere
    {
        if (empty(trim($motif))) {
            throw new \DomainException('Le motif d\'annulation est obligatoire.');
        }

        return $this->circuit->transitionner(
            $mouvement,
            StatutMouvement::ANNULE,
            'annulation',
            $motif,
        );
    }
}