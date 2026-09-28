<?php

namespace App\Domain\Paie\Enums;

enum TauxHeureSupp: string
{
    case HS20_JOUR  = 'hs20';
    case HS40_JOUR  = 'hs40';
    case HS65_JOUR  = 'hs65_jour';
    case HS65_NUIT  = 'hs65_nuit';
    case HS100      = 'hs100';

    public function libelle(): string
    {
        return match ($this) {
            self::HS20_JOUR => 'HS 20 % (jour)',
            self::HS40_JOUR => 'HS 40 % (jour)',
            self::HS65_JOUR => 'HS 65 % (jour)',
            self::HS65_NUIT => 'HS 65 % (nuit)',
            self::HS100     => 'HS 100 %',
        };
    }

    public function coefficient(): float
    {
        return match ($this) {
            self::HS20_JOUR => 1.20,
            self::HS40_JOUR => 1.40,
            self::HS65_JOUR => 1.65,
            self::HS65_NUIT => 1.65,
            self::HS100     => 2.00,
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($e) => [$e->value => $e->libelle()])->all();
    }
}