<?php

namespace App\Domain\Contrats\Actions;

use App\Domain\Contrats\Enums\StatutContrat;
use App\Domain\Contrats\Models\Contrat;
use App\Domain\Contrats\Services\CircuitContrat;

class AnnulerContrat
{
    public function __construct(private CircuitContrat $circuit) {}

    public function executer(Contrat $contrat, string $motif): Contrat
    {
        if (empty(trim($motif))) {
            throw new \DomainException('Le motif d\'annulation est obligatoire.');
        }

        return $this->circuit->transitionner(
            $contrat,
            StatutContrat::ANNULE,
            'annulation',
            $motif,
        );
    }
}