<?php

namespace App\Domain\Contrats\Enums;

enum NatureEvenementEssai: string
{
    case SUSPENSION   = 'suspension';
    case RENOUVELLEMENT = 'renouvellement';
    case CONFIRMATION = 'confirmation';
    case RUPTURE      = 'rupture';

    public function libelle(): string
    {
        return match ($this) {
            self::SUSPENSION    => 'Suspension',
            self::RENOUVELLEMENT => 'Renouvellement',
            self::CONFIRMATION  => 'Confirmation',
            self::RUPTURE       => 'Rupture',
        };
    }

    public function couleur(): string
    {
        return match ($this) {
            self::SUSPENSION    => 'warning',
            self::RENOUVELLEMENT => 'info',
            self::CONFIRMATION  => 'success',
            self::RUPTURE       => 'danger',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($e) => [$e->value => $e->libelle()])->all();
    }
}