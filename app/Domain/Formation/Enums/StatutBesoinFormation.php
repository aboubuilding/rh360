<?php

namespace App\Domain\Formation\Enums;

enum StatutBesoinFormation: string
{
    case A_ETUDIER   = 'a_etudier';
    case VALIDE      = 'valide';
    case PLANIFIE    = 'planifie';
    case REALISE     = 'realise';
    case ANNULE      = 'annule';

    public function libelle(): string
    {
        return match ($this) {
            self::A_ETUDIER => 'À étudier',
            self::VALIDE    => 'Validé',
            self::PLANIFIE  => 'Planifié',
            self::REALISE   => 'Réalisé',
            self::ANNULE    => 'Annulé',
        };
    }

    public function couleur(): string
    {
        return match ($this) {
            self::A_ETUDIER => 'secondary',
            self::VALIDE    => 'info',
            self::PLANIFIE  => 'primary',
            self::REALISE   => 'success',
            self::ANNULE    => 'danger',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($e) => [$e->value => $e->libelle()])->all();
    }
}