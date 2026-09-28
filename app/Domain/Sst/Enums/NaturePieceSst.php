<?php

namespace App\Domain\Sst\Enums;

enum NaturePieceSst: string
{
    case VISITE_MEDICALE       = 'visite_medicale';
    case EVENEMENT_SECURITE    = 'evenement_securite';
    case RISQUE                = 'risque';
    case DOTATION_EPI          = 'dotation_epi';
    case HABILITATION          = 'habilitation';
    case ACTION                = 'action';

    public function libelle(): string
    {
        return match ($this) {
            self::VISITE_MEDICALE    => 'Visite médicale',
            self::EVENEMENT_SECURITE => 'Événement sécurité',
            self::RISQUE             => 'Risque',
            self::DOTATION_EPI       => 'Dotation EPI',
            self::HABILITATION       => 'Habilitation',
            self::ACTION             => 'Action',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($e) => [$e->value => $e->libelle()])->all();
    }
}