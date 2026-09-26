<?php

namespace App\Domain\Contrats\Enums;

enum TypeContrat: string
{
    case CDI          = 'CDI';
    case CDD          = 'CDD';
    case STAGE        = 'Stage';
    case APPRENTISSAGE= 'Apprentissage';
    case INTERIM      = 'Intérim';
    case CONSULTANT   = 'Consultant';
    case VACATAIRE    = 'Vacataire';

    public function estDureeDeterminee(): bool
    {
        return in_array($this, [self::CDD, self::STAGE, self::APPRENTISSAGE, self::INTERIM, self::VACATAIRE], true);
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($e) => [$e->value => $e->value])->all();
    }
}