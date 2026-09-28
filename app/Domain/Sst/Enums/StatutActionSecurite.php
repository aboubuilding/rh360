<?php

namespace App\Domain\Sst\Enums;

enum StatutActionSecurite: string
{
    case A_FAIRE    = 'todo';
    case EN_COURS   = 'in_progress';
    case REALISEE   = 'done';
    case ANNULEE    = 'cancelled';

    public function libelle(): string
    {
        return match ($this) {
            self::A_FAIRE  => 'À faire',
            self::EN_COURS => 'En cours',
            self::REALISEE => 'Réalisée',
            self::ANNULEE  => 'Annulée',
        };
    }

    public function couleur(): string
    {
        return match ($this) {
            self::A_FAIRE  => 'secondary',
            self::EN_COURS => 'primary',
            self::REALISEE => 'success',
            self::ANNULEE  => 'danger',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($e) => [$e->value => $e->libelle()])->all();
    }
}