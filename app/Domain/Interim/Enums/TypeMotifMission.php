<?php

namespace App\Domain\Interim\Enums;

/**
 * Nature du motif d'une mission d'intérim. Le motif lui-même est décrit en clair ;
 * le remplacement d'un gréviste n'existe ici que pour être refusé (RG-TT-05).
 */
enum TypeMotifMission: string
{
    case REMPLACEMENT_ABSENCE = 'remplacement_absence';
    case REMPLACEMENT_GREVISTE = 'remplacement_greviste';
    case AUTRE = 'autre';

    public function libelle(): string
    {
        return match ($this) {
            self::REMPLACEMENT_ABSENCE => 'Remplacement d\'un salarié absent',
            self::REMPLACEMENT_GREVISTE => 'Remplacement d\'un salarié gréviste',
            self::AUTRE => 'Autre motif (à préciser)',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($e) => [$e->value => $e->libelle()])->all();
    }
}
