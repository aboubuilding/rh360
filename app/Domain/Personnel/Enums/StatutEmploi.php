<?php

namespace App\Domain\Personnel\Enums;

enum StatutEmploi: string
{
    case ACTIF    = 'Actif';
    case SUSPENDU = 'Suspendu';
    case SORTI    = 'Sorti';
    case RETRAITE = 'Retraité';

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($e) => [$e->value => $e->value])->all();
    }
}