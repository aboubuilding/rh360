<?php

namespace App\Domain\Carriere\Actions;

use App\Domain\Carriere\Enums\StatutMouvement;
use App\Domain\Carriere\Events\MouvementRejete;
use App\Domain\Carriere\Models\MouvementCarriere;
use App\Domain\Carriere\Services\CircuitMouvement;

class RejeterMouvement
{
    public function __construct(private CircuitMouvement $circuit) {}

    public function executer(MouvementCarriere $mouvement, string $motif): MouvementCarriere
    {
        if (empty(trim($motif))) {
            throw new \DomainException('Le motif de rejet est obligatoire.');
        }

        $mouvement = $this->circuit->transitionner(
            $mouvement,
            StatutMouvement::REJETE,
            'rejet',
            $motif,
        );

        event(new MouvementRejete($mouvement, $motif));

        return $mouvement;
    }
}