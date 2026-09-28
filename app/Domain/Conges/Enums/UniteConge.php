<?php

namespace App\Domain\Conges\Enums;

enum UniteConge: string
{
    case JOUR_CALENDAIRE = 'jour_calendaire';
    case JOUR_OUVRABLE   = 'jour_ouvrable';
    case HEURE           = 'heure';

    public function libelle(): string
    {
        return match ($this) {
            self::JOUR_CALENDAIRE => 'Jour calendaire',
            self::JOUR_OUVRABLE   => 'Jour ouvrable',
            self::HEURE           => 'Heure',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($e) => [$e->value => $e->libelle()])->all();
    }
}