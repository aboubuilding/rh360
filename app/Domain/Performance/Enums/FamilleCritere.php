<?php

namespace App\Domain\Performance\Enums;

enum FamilleCritere: string
{
    case COMPETENCE   = 'competence';
    case COMPORTEMENT = 'comportement';
    case RESULTAT     = 'resultat';
    case POTENTIEL    = 'potentiel';

    public function libelle(): string
    {
        return match ($this) {
            self::COMPETENCE   => 'Compétence',
            self::COMPORTEMENT => 'Comportement',
            self::RESULTAT     => 'Résultat',
            self::POTENTIEL    => 'Potentiel',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($e) => [$e->value => $e->libelle()])->all();
    }
}