<?php

namespace App\Domain\Conges\Actions;

use App\Domain\Conges\Enums\StatutDemandeConge;
use App\Domain\Conges\Models\DemandeConge;
use App\Domain\Conges\Services\CircuitDemandeConge;

class AnnulerDemandeConge
{
    public function __construct(private CircuitDemandeConge $circuit) {}

    public function executer(DemandeConge $demande, string $motif): DemandeConge
    {
        if (empty(trim($motif))) {
            throw new \DomainException('Le motif d\'annulation est obligatoire.');
        }

        return $this->circuit->transitionner($demande, StatutDemandeConge::ANNULEE, $motif);
    }
}