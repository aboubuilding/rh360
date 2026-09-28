<?php

namespace App\Domain\Carriere\Enums;

enum StatutMouvement: string
{
    case BROUILLON  = 'draft';
    case PROPOSE    = 'proposed';
    case A_VERIFIER = 'to_check';
    case VERIFIE    = 'checked';
    case VALIDE     = 'validated';
    case PROGRAMME  = 'scheduled';
    case EFFECTIF   = 'effective';
    case TERMINE    = 'completed';
    case REJETE     = 'rejected';
    case ANNULE     = 'cancelled';

    public function libelle(): string
    {
        return match ($this) {
            self::BROUILLON  => 'Brouillon',
            self::PROPOSE    => 'Proposé',
            self::A_VERIFIER => 'À vérifier',
            self::VERIFIE    => 'Vérifié',
            self::VALIDE     => 'Validé',
            self::PROGRAMME  => 'Programmé',
            self::EFFECTIF   => 'Effectif',
            self::TERMINE    => 'Terminé',
            self::REJETE     => 'Rejeté',
            self::ANNULE     => 'Annulé',
        };
    }

    public function couleur(): string
    {
        return match ($this) {
            self::BROUILLON  => 'secondary',
            self::PROPOSE    => 'info',
            self::A_VERIFIER => 'warning',
            self::VERIFIE    => 'primary',
            self::VALIDE     => 'success',
            self::PROGRAMME  => 'primary',
            self::EFFECTIF   => 'success',
            self::TERMINE    => 'dark',
            self::REJETE     => 'danger',
            self::ANNULE     => 'danger',
        };
    }

    public function estModifiable(): bool
    {
        return in_array($this, [self::BROUILLON, self::PROPOSE], true);
    }

    public function estEnCircuit(): bool
    {
        return in_array($this, [
            self::A_VERIFIER, self::VERIFIE, self::VALIDE, self::PROGRAMME,
        ], true);
    }

    public function estFinal(): bool
    {
        return in_array($this, [self::EFFECTIF, self::TERMINE, self::REJETE, self::ANNULE], true);
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($e) => [$e->value => $e->libelle()])->all();
    }
}