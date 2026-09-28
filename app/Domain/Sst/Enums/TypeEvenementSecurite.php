<?php

namespace App\Domain\Sst\Enums;

enum TypeEvenementSecurite: string
{
    case ACCIDENT_TRAVAIL     = 'work_accident';
    case ACCIDENT_TRAJET      = 'commute_accident';
    case INCIDENT             = 'incident';
    case PRESQUE_ACCIDENT     = 'near_miss';
    case MALADIE_PROFESSIONNELLE = 'occupational_disease';
    case SITUATION_DANGEREUSE = 'dangerous_situation';

    public function libelle(): string
    {
        return match ($this) {
            self::ACCIDENT_TRAVAIL         => 'Accident du travail',
            self::ACCIDENT_TRAJET          => 'Accident de trajet',
            self::INCIDENT                 => 'Incident',
            self::PRESQUE_ACCIDENT         => 'Presque-accident',
            self::MALADIE_PROFESSIONNELLE  => 'Maladie professionnelle',
            self::SITUATION_DANGEREUSE     => 'Situation dangereuse',
        };
    }

    public function couleur(): string
    {
        return match ($this) {
            self::ACCIDENT_TRAVAIL         => 'danger',
            self::ACCIDENT_TRAJET          => 'danger',
            self::INCIDENT                 => 'warning',
            self::PRESQUE_ACCIDENT         => 'info',
            self::MALADIE_PROFESSIONNELLE  => 'danger',
            self::SITUATION_DANGEREUSE     => 'warning',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($e) => [$e->value => $e->libelle()])->all();
    }
}