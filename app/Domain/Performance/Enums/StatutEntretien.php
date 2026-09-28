<?php

namespace App\Domain\Performance\Enums;

enum StatutEntretien: string
{
    case A_PREPARER  = 'a_preparer';
    case AUTO_EVALUE = 'auto_evalue';
    case REALISE     = 'realise';
    case VALIDE      = 'valide';
    case ANNULE      = 'annule';

    public function libelle(): string
    {
        return match ($this) {
            self::A_PREPARER  => 'À préparer',
            self::AUTO_EVALUE => 'Auto-évalué',
            self::REALISE     => 'Entretien réalisé',
            self::VALIDE      => 'Validé',
            self::ANNULE      => 'Annulé',
        };
    }

    public function couleur(): string
    {
        return match ($this) {
            self::A_PREPARER  => 'secondary',
            self::AUTO_EVALUE => 'info',
            self::REALISE     => 'primary',
            self::VALIDE      => 'success',
            self::ANNULE      => 'danger',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($e) => [$e->value => $e->libelle()])->all();
    }
}