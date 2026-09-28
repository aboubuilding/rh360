<?php

namespace App\Domain\Conges\Enums;

enum TraitementSalarial: string
{
    case MAINTIEN       = 'maintien';
    case NON_REMUNERE   = 'non_remunere';
    case INDEMNITE      = 'indemnite';
    case PARTIEL        = 'partiel';

    public function libelle(): string
    {
        return match ($this) {
            self::MAINTIEN     => 'Maintien du salaire',
            self::NON_REMUNERE => 'Non rémunéré',
            self::INDEMNITE    => 'Indemnité',
            self::PARTIEL      => 'Partiellement rémunéré',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($e) => [$e->value => $e->libelle()])->all();
    }
}