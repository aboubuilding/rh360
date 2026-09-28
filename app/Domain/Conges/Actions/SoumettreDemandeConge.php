<?php

namespace App\Domain\Conges\Actions;

use App\Domain\Conges\Enums\StatutDemandeConge;
use App\Domain\Conges\Models\DemandeConge;
use App\Domain\Conges\Services\CircuitDemandeConge;
use App\Domain\Conges\Services\ValidateurDroitConge;

class SoumettreDemandeConge
{
    public function __construct(
        private CircuitDemandeConge $circuit,
        private ValidateurDroitConge $validateur,
    ) {}

    public function executer(DemandeConge $demande): DemandeConge
    {
        // Vérifier les contraintes du type
        $contraintes = $this->validateur->verifierContraintesType($demande);
        if (! $contraintes['ok']) {
            throw new \DomainException($contraintes['message']);
        }

        return $this->circuit->transitionner($demande, StatutDemandeConge::SOUMISE);
    }
}