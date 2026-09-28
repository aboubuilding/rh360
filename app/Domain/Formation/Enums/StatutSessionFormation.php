<?php

namespace App\Domain\Formation\Enums;

enum StatutSessionFormation: string
{
    case PROGRAMMEE = 'programmee';
    case EN_COURS   = 'en_cours';
    case TERMINEE   = 'terminee';
    case ANNULEE    = 'annulee';

    public function libelle(): string
    {
        return match ($this) {
            self::PROGRAMMEE => 'Programmée',
            self::EN_COURS   => 'En cours',
            self::TERMINEE   => 'Terminée',
            self::ANNULEE    => 'Annulée',
        };
    }

    public function couleur(): string
    {
        return match ($this) {
            self::PROGRAMMEE => 'info',
            self::EN_COURS   => 'primary',
            self::TERMINEE   => 'success',
            self::ANNULEE    => 'danger',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($e) => [$e->value => $e->libelle()])->all();
    }
}