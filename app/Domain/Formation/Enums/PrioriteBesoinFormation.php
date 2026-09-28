<?php

namespace App\Domain\Formation\Enums;

enum PrioriteBesoinFormation: string
{
    case BASSE    = 'basse';
    case NORMALE  = 'normale';
    case HAUTE    = 'haute';
    case CRITIQUE = 'critique';

    public function libelle(): string
    {
        return match ($this) {
            self::BASSE    => 'Basse',
            self::NORMALE  => 'Normale',
            self::HAUTE    => 'Haute',
            self::CRITIQUE => 'Critique',
        };
    }

    public function couleur(): string
    {
        return match ($this) {
            self::BASSE    => 'secondary',
            self::NORMALE  => 'info',
            self::HAUTE    => 'warning',
            self::CRITIQUE => 'danger',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($e) => [$e->value => $e->libelle()])->all();
    }
}