<?php

namespace App\Domain\Paie\Enums;

enum NatureRubrique: string
{
    case GAIN        = 'gain';
    case RETENUE     = 'retenue';
    case COTISATION  = 'cotisation';

    public function libelle(): string
    {
        return match ($this) {
            self::GAIN       => 'Gain',
            self::RETENUE    => 'Retenue',
            self::COTISATION => 'Cotisation',
        };
    }

    public function couleur(): string
    {
        return match ($this) {
            self::GAIN       => 'success',
            self::RETENUE    => 'danger',
            self::COTISATION => 'warning',
        };
    }

    public function impacteBrut(): bool
    {
        return $this === self::GAIN;
    }

    public function impacteNet(): bool
    {
        return in_array($this, [self::RETENUE, self::COTISATION], true);
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($e) => [$e->value => $e->libelle()])->all();
    }
}