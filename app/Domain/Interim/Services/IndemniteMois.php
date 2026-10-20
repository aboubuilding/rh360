<?php

namespace App\Domain\Interim\Services;

use Carbon\Carbon;

/**
 * Indemnité d'intérim calculée pour un mois de paie (montants entiers en FCFA).
 */
final class IndemniteMois
{
    public function __construct(
        public readonly int $montant,
        public readonly int $mensuel,
        public readonly int $minimum,
        public readonly int $salaireBase,
        public readonly Carbon $du,
        public readonly Carbon $au,
        public readonly int $jours,
        public readonly int $joursBase,
    ) {}

    public function estProratisee(): bool
    {
        return $this->jours !== $this->joursBase;
    }
}
