<?php

namespace App\Domain\Conges\Enums;

/**
 * Période sur laquelle s'apprécie le plafond d'un type de demande.
 */
enum PeriodePlafond: string
{
    case ANNEE_CIVILE  = 'annee_civile';
    case ANNEE_SERVICE = 'annee_service';
    case MOIS          = 'mois';
    case EVENEMENT     = 'evenement';
    case CARRIERE      = 'carriere';

    public function libelle(): string
    {
        return match ($this) {
            self::ANNEE_CIVILE  => 'Par année civile',
            self::ANNEE_SERVICE => 'Par année de service',
            self::MOIS          => 'Par mois',
            self::EVENEMENT     => 'Par événement',
            self::CARRIERE      => 'Sur la carrière',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($e) => [$e->value => $e->libelle()])->all();
    }
}
