<?php

namespace App\Domain\Sst\Enums;

enum TypeVisiteMedicale: string
{
    case EMBAUCHE    = 'embauche';
    case PERIODIQUE  = 'periodique';
    case REPRISE     = 'reprise';
    case DEMANDE     = 'a_la_demande';
    case SORTIE      = 'sortie';

    public function libelle(): string
    {
        return match ($this) {
            self::EMBAUCHE   => 'Visite d\'embauche',
            self::PERIODIQUE => 'Visite périodique',
            self::REPRISE    => 'Visite de reprise',
            self::DEMANDE    => 'Visite à la demande',
            self::SORTIE     => 'Visite de sortie',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($e) => [$e->value => $e->libelle()])->all();
    }
}