<?php

namespace App\Domain\Personnel\Enums;

enum LienParente: string
{
    case CONJOINT  = 'Conjoint(e)';
    case ENFANT    = 'Enfant';
    case PERE      = 'Père';
    case MERE      = 'Mère';
    case FRERE     = 'Frère';
    case SOEUR     = 'Sœur';
    case AUTRE     = 'Autre';

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($e) => [$e->value => $e->value])->all();
    }
}