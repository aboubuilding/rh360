<?php

namespace App\Domain\Contrats\Actions;

use App\Domain\Contrats\Enums\StatutContrat;
use App\Domain\Contrats\Models\Contrat;
use App\Domain\Contrats\Services\CircuitContrat;

class SoumettreContrat
{
    public function __construct(private CircuitContrat $circuit) {}

    public function executer(Contrat $contrat): Contrat
    {
        return $this->circuit->transitionner(
            $contrat,
            StatutContrat::SOUMIS,
            'soumission',
        );
    }
}