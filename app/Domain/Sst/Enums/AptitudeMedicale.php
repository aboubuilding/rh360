<?php

namespace App\Domain\Sst\Enums;

enum AptitudeMedicale: string
{
    case EN_ATTENTE      = 'pending';
    case APTE            = 'fit';
    case APTE_AVEC_RESTRICTIONS = 'fit_with_restrictions';
    case INAPTE_TEMPORAIRE = 'temporarily_unfit';
    case INAPTE_DEFINITIF  = 'permanently_unfit';

    public function libelle(): string
    {
        return match ($this) {
            self::EN_ATTENTE            => 'En attente',
            self::APTE                  => 'Apte',
            self::APTE_AVEC_RESTRICTIONS => 'Apte avec restrictions',
            self::INAPTE_TEMPORAIRE     => 'Inapte temporaire',
            self::INAPTE_DEFINITIF      => 'Inapte définitif',
        };
    }

    public function couleur(): string
    {
        return match ($this) {
            self::EN_ATTENTE            => 'secondary',
            self::APTE                  => 'success',
            self::APTE_AVEC_RESTRICTIONS => 'warning',
            self::INAPTE_TEMPORAIRE     => 'warning',
            self::INAPTE_DEFINITIF      => 'danger',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($e) => [$e->value => $e->libelle()])->all();
    }
}