<?php

namespace App\Domain\Contrats\Actions;

use App\Domain\Contrats\Enums\StatutContrat;
use App\Domain\Contrats\Events\ContratValide;
use App\Domain\Contrats\Models\Contrat;
use App\Domain\Contrats\Services\CircuitContrat;

class ValiderContrat
{
    public function __construct(private CircuitContrat $circuit) {}

    public function executer(Contrat $contrat, ?string $noteDerogation = null): Contrat
    {
        $contrat = $this->circuit->transitionner(
            $contrat,
            StatutContrat::VALIDE,
            'validation',
            $noteDerogation,
        );

        event(new ContratValide($contrat, $noteDerogation));

        return $contrat;
    }
}