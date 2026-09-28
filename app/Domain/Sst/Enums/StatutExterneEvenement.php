<?php

namespace App\Domain\Sst\Enums;

enum StatutExterneEvenement: string
{
    case AUCUN         = 'none';
    case A_TRANSMETTRE = 'to_submit';
    case TRANSMIS      = 'submitted';
    case ACCEPTE       = 'accepted';
    case REJETE        = 'rejected';

    public function libelle(): string
    {
        return match ($this) {
            self::AUCUN         => 'Aucune démarche externe',
            self::A_TRANSMETTRE => 'À transmettre',
            self::TRANSMIS      => 'Transmis',
            self::ACCEPTE       => 'Accepté',
            self::REJETE        => 'Rejeté',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($e) => [$e->value => $e->libelle()])->all();
    }
}