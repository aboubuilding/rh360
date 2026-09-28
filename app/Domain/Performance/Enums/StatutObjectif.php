<?php

namespace App\Domain\Performance\Enums;

enum StatutObjectif: string
{
    case A_REALISER = 'a_realiser';
    case EN_COURS   = 'en_cours';
    case ATTEINT    = 'atteint';
    case PARTIEL    = 'partiel';
    case NON_ATTEINT = 'non_atteint';
    case ANNULE     = 'annule';

    public function libelle(): string
    {
        return match ($this) {
            self::A_REALISER  => 'À réaliser',
            self::EN_COURS    => 'En cours',
            self::ATTEINT     => 'Atteint',
            self::PARTIEL     => 'Partiellement atteint',
            self::NON_ATTEINT => 'Non atteint',
            self::ANNULE      => 'Annulé',
        };
    }

    public function couleur(): string
    {
        return match ($this) {
            self::A_REALISER  => 'secondary',
            self::EN_COURS    => 'primary',
            self::ATTEINT     => 'success',
            self::PARTIEL     => 'warning',
            self::NON_ATTEINT => 'danger',
            self::ANNULE      => 'dark',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($e) => [$e->value => $e->libelle()])->all();
    }
}