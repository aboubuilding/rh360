<?php

namespace App\Domain\Paie\Enums;

enum TypeRubriqueSysteme: string
{
    case SALAIRE_BASE      = 'salaire_base';
    case PRIME_ANCIENNETE  = 'prime_anciennete';
    case HEURES_SUPP       = 'heures_supplementaires';
    case CNSS_SALARIAL     = 'cnss_salarial';
    case AMU_SALARIAL      = 'amu_salarial';
    case IRPP              = 'irpp';
    case RAPPEL_AVANCEMENT = 'rappel_avancement';

    public function libelle(): string
    {
        return match ($this) {
            self::SALAIRE_BASE      => 'Salaire de base',
            self::PRIME_ANCIENNETE  => 'Prime d\'ancienneté',
            self::HEURES_SUPP       => 'Heures supplémentaires',
            self::CNSS_SALARIAL     => 'CNSS (part salariale)',
            self::AMU_SALARIAL      => 'AMU (part salariale)',
            self::IRPP              => 'IRPP',
            self::RAPPEL_AVANCEMENT => 'Rappel d\'avancement',
        };
    }
}