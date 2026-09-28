<?php

namespace App\Domain\Carriere\Enums;

enum StatutHistorique: string
{
    case NON_RECUPERE = 'not_recovered';
    case PARTIEL      = 'partial';
    case COMPLET      = 'complete';

    public function libelle(): string
    {
        return match ($this) {
            self::NON_RECUPERE => 'Non récupéré',
            self::PARTIEL      => 'Partiel',
            self::COMPLET      => 'Complet',
        };
    }

    public function couleur(): string
    {
        return match ($this) {
            self::NON_RECUPERE => 'danger',
            self::PARTIEL      => 'warning',
            self::COMPLET      => 'success',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($e) => [$e->value => $e->libelle()])->all();
    }
}