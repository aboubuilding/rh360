<?php

namespace App\Domain\Sst\Enums;

enum FamilleRisque: string
{
    case PHYSIQUE    = 'physique';
    case CHIMIQUE    = 'chimique';
    case BIOLOGIQUE  = 'biologique';
    case ERGONOMIQUE = 'ergonomique';
    case PSYCHOSOCIAL = 'psychosocial';
    case ELECTRIQUE  = 'electrique';
    case MECANIQUE   = 'mecanique';
    case AUTRE       = 'autre';

    public function libelle(): string
    {
        return match ($this) {
            self::PHYSIQUE     => 'Physique',
            self::CHIMIQUE     => 'Chimique',
            self::BIOLOGIQUE   => 'Biologique',
            self::ERGONOMIQUE  => 'Ergonomique',
            self::PSYCHOSOCIAL => 'Psychosocial',
            self::ELECTRIQUE   => 'Électrique',
            self::MECANIQUE    => 'Mécanique',
            self::AUTRE        => 'Autre',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($e) => [$e->value => $e->libelle()])->all();
    }
}