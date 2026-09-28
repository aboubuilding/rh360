<?php

namespace App\Domain\Carriere\Enums;

enum TypeEvolution: string
{
    case ECHELON   = 'echelon';
    case CLASSE    = 'classe';
    case CATEGORIE = 'categorie';

    public function libelle(): string
    {
        return match ($this) {
            self::ECHELON   => 'Échelon',
            self::CLASSE    => 'Classe',
            self::CATEGORIE => 'Catégorie',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($e) => [$e->value => $e->libelle()])->all();
    }
}