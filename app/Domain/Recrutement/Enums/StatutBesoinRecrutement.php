<?php

namespace App\Domain\Recrutement\Enums;

enum StatutBesoinRecrutement: string
{
    case A_VALIDER  = 'a_valider';
    case VALIDE     = 'valide';
    case EN_COURS   = 'en_cours';
    case POURVU     = 'pourvu';
    case ANNULE     = 'annule';

    public function libelle(): string
    {
        return match ($this) {
            self::A_VALIDER => 'À valider',
            self::VALIDE    => 'Validé',
            self::EN_COURS  => 'En cours de recrutement',
            self::POURVU    => 'Pourvu',
            self::ANNULE    => 'Annulé',
        };
    }

    public function couleur(): string
    {
        return match ($this) {
            self::A_VALIDER => 'secondary',
            self::VALIDE    => 'info',
            self::EN_COURS  => 'primary',
            self::POURVU    => 'success',
            self::ANNULE    => 'danger',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($e) => [$e->value => $e->libelle()])->all();
    }
}