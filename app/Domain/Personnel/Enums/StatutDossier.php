<?php

namespace App\Domain\Personnel\Enums;

enum StatutDossier: string
{
    case INCOMPLET = 'incomplete';
    case COMPLET   = 'complete';

    public function libelle(): string
    {
        return match ($this) {
            self::INCOMPLET => 'À compléter',
            self::COMPLET   => 'Complet',
        };
    }

    public function couleur(): string
    {
        return match ($this) {
            self::INCOMPLET => 'warning',
            self::COMPLET   => 'success',
        };
    }
}