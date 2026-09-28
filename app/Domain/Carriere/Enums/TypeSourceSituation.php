<?php

namespace App\Domain\Carriere\Enums;

enum TypeSourceSituation: string
{
    case MANUEL       = 'manual';
    case ACTE         = 'acte';
    case REPRISE      = 'reprise';
    case IMPORT       = 'import';
    case AUTOMATIQUE  = 'automatic';

    public function libelle(): string
    {
        return match ($this) {
            self::MANUEL      => 'Saisie manuelle',
            self::ACTE        => 'Acte de carrière',
            self::REPRISE     => 'Reprise d\'historique',
            self::IMPORT      => 'Import',
            self::AUTOMATIQUE => 'Application automatique',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($e) => [$e->value => $e->libelle()])->all();
    }
}