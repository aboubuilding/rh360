<?php

namespace App\Domain\Sst\Enums;

enum StatutDotationEpi: string
{
    case REMIS      = 'issued';
    case EN_USAGE   = 'in_use';
    case A_REMPLACER = 'to_replace';
    case RESTITUE   = 'returned';
    case PERDU      = 'lost';
    case REBUTE     = 'discarded';

    public function libelle(): string
    {
        return match ($this) {
            self::REMIS       => 'Remis',
            self::EN_USAGE    => 'En usage',
            self::A_REMPLACER => 'À remplacer',
            self::RESTITUE    => 'Restitué',
            self::PERDU       => 'Perdu',
            self::REBUTE      => 'Mis au rebut',
        };
    }

    public function couleur(): string
    {
        return match ($this) {
            self::REMIS       => 'info',
            self::EN_USAGE    => 'primary',
            self::A_REMPLACER => 'warning',
            self::RESTITUE    => 'success',
            self::PERDU       => 'danger',
            self::REBUTE      => 'secondary',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($e) => [$e->value => $e->libelle()])->all();
    }
}