<?php

namespace App\Domain\Shared\Enums;

enum Etat: int
{
    case SUPPRIME = -1;
    case INACTIF  =  0;
    case ACTIF    =  1;

    public function libelle(): string
    {
        return match ($this) {
            self::ACTIF    => 'Actif',
            self::INACTIF  => 'Inactif',
            self::SUPPRIME => 'Supprimé',
        };
    }

    public function couleur(): string
    {
        return match ($this) {
            self::ACTIF    => 'green',
            self::INACTIF  => 'gray',
            self::SUPPRIME => 'red',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($e) => [$e->value => $e->libelle()])
            ->all();
    }
}