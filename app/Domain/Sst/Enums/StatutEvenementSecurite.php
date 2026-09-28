<?php

namespace App\Domain\Sst\Enums;

enum StatutEvenementSecurite: string
{
    case DECLARE      = 'declared';
    case EN_TRAITEMENT = 'in_progress';
    case CLOTURE      = 'closed';
    case ANNULE       = 'cancelled';

    public function libelle(): string
    {
        return match ($this) {
            self::DECLARE       => 'Déclaré',
            self::EN_TRAITEMENT => 'En traitement',
            self::CLOTURE       => 'Clôturé',
            self::ANNULE        => 'Annulé',
        };
    }

    public function couleur(): string
    {
        return match ($this) {
            self::DECLARE       => 'warning',
            self::EN_TRAITEMENT => 'primary',
            self::CLOTURE       => 'success',
            self::ANNULE        => 'secondary',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($e) => [$e->value => $e->libelle()])->all();
    }
}