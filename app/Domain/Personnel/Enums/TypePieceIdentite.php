<?php

namespace App\Domain\Personnel\Enums;

enum TypePieceIdentite: string
{
    case CNI        = 'CNI';
    case PASSEPORT  = 'Passeport';
    case CARTE_SEJ  = 'Carte de séjour';
    case ACTE_NAIS  = 'Acte de naissance';
    case AUTRE      = 'Autre';

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($e) => [$e->value => $e->value])->all();
    }
}