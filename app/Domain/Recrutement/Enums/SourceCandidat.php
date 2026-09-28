<?php

namespace App\Domain\Recrutement\Enums;

enum SourceCandidat: string
{
    case SPONTANEE    = 'spontanee';
    case ANNONCE      = 'annonce';
    case RECOMMANDATION = 'recommandation';
    case CABINET      = 'cabinet';
    case RESEAUX      = 'reseaux';
    case ECOLE        = 'ecole';
    case AUTRE        = 'autre';

    public function libelle(): string
    {
        return match ($this) {
            self::SPONTANEE      => 'Candidature spontanée',
            self::ANNONCE        => 'Annonce',
            self::RECOMMANDATION => 'Recommandation',
            self::CABINET        => 'Cabinet de recrutement',
            self::RESEAUX        => 'Réseaux sociaux',
            self::ECOLE          => 'École / université',
            self::AUTRE          => 'Autre',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($e) => [$e->value => $e->libelle()])->all();
    }
}