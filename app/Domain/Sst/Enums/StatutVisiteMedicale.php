<?php

namespace App\Domain\Sst\Enums;

enum StatutVisiteMedicale: string
{
    case PLANIFIEE    = 'planned';
    case REALISEE     = 'completed';
    case ANNULEE      = 'cancelled';
    case NON_REALISEE = 'not_done';

    public function libelle(): string
    {
        return match ($this) {
            self::PLANIFIEE    => 'Planifiée',
            self::REALISEE     => 'Réalisée',
            self::ANNULEE      => 'Annulée',
            self::NON_REALISEE => 'Non réalisée',
        };
    }

    public function couleur(): string
    {
        return match ($this) {
            self::PLANIFIEE    => 'primary',
            self::REALISEE     => 'success',
            self::ANNULEE      => 'danger',
            self::NON_REALISEE => 'warning',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($e) => [$e->value => $e->libelle()])->all();
    }
}