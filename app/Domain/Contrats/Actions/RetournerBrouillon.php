<?php

namespace App\Domain\Contrats\Actions;

use App\Domain\Contrats\Enums\StatutContrat;
use App\Domain\Contrats\Models\Contrat;
use App\Domain\Contrats\Services\CircuitContrat;

class RetournerBrouillon
{
    public function __construct(private CircuitContrat $circuit) {}

    public function executer(Contrat $contrat, string $motif): Contrat
    {
        if (empty(trim($motif))) {
            throw new \DomainException('Le motif de retour au brouillon est obligatoire.');
        }

        return $this->circuit->transitionner(
            $contrat,
            StatutContrat::BROUILLON,
            'retour_brouillon',
            $motif,
        );
    }
}