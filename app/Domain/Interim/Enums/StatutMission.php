<?php

namespace App\Domain\Interim\Enums;

enum StatutMission: string
{
    case PLANIFIEE = 'planifiee';
    case EN_COURS = 'en_cours';
    case TERMINEE = 'terminee';
    case ANNULEE = 'annulee';

    public function libelle(): string
    {
        return match ($this) {
            self::PLANIFIEE => 'Planifiée',
            self::EN_COURS => 'En cours',
            self::TERMINEE => 'Terminée',
            self::ANNULEE => 'Annulée',
        };
    }

    public function couleur(): string
    {
        return match ($this) {
            self::PLANIFIEE => 'info',
            self::EN_COURS => 'primary',
            self::TERMINEE => 'secondary',
            self::ANNULEE => 'dark',
        };
    }

    public function estFinal(): bool
    {
        return in_array($this, [self::TERMINEE, self::ANNULEE], true);
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($e) => [$e->value => $e->libelle()])->all();
    }
}
