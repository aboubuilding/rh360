<?php

namespace App\Domain\Conges\Actions;

use App\Domain\Conges\Enums\StatutDemandeConge;
use App\Domain\Conges\Models\DemandeConge;
use App\Domain\Conges\Services\CircuitDemandeConge;

class ProgrammerDemandeConge
{
    public function __construct(private CircuitDemandeConge $circuit) {}

    public function executer(DemandeConge $demande): DemandeConge
    {
        return $this->circuit->transitionner($demande, StatutDemandeConge::PROGRAMMEE);
    }
}