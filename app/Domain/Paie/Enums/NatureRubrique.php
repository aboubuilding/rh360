<?php

namespace App\Domain\Paie\Enums;

enum NatureRubrique: string
{
    case GAIN        = 'gain';
    case RETENUE     = 'retenue';
    case COTISATION  = 'cotisation';
    // Part patronale : affichée sur le bulletin, jamais déduite du salaire (CCIT art. 35).
    case COTISATION_PATRONALE = 'cotisation_patronale';

    public function libelle(): string
    {
        return match ($this) {
            self::GAIN       => 'Gain',
            self::RETENUE    => 'Retenue',
            self::COTISATION => 'Cotisation',
            self::COTISATION_PATRONALE => 'Cotisation patronale',
        };
    }

    public function couleur(): string
    {
        return match ($this) {
            self::GAIN       => 'success',
            self::RETENUE    => 'danger',
            self::COTISATION => 'warning',
            self::COTISATION_PATRONALE => 'secondary',
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
        // Les cotisations patronales sont produites par le moteur, elles ne se saisissent pas comme rubrique.
        return collect(self::cases())->reject(fn ($e) => $e === self::COTISATION_PATRONALE)->mapWithKeys(fn ($e) => [$e->value => $e->libelle()])->all();
    }
}