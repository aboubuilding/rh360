<?php

namespace App\Domain\Conges\Enums;

enum StatutTransmissionPaie: string
{
    case A_PREPARER = 'a_preparer';
    case PREPARE    = 'prepare';
    case TRANSMIS   = 'transmis';
    case INTEGRE    = 'integre';

    public function libelle(): string
    {
        return match ($this) {
            self::A_PREPARER => 'À préparer',
            self::PREPARE    => 'Préparé',
            self::TRANSMIS   => 'Transmis',
            self::INTEGRE    => 'Intégré à la paie',
        };
    }

    public function couleur(): string
    {
        return match ($this) {
            self::A_PREPARER => 'secondary',
            self::PREPARE    => 'info',
            self::TRANSMIS   => 'primary',
            self::INTEGRE    => 'success',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($e) => [$e->value => $e->libelle()])->all();
    }
}