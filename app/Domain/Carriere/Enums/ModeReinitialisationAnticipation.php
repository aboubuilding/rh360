<?php

namespace App\Domain\Carriere\Enums;

enum ModeReinitialisationAnticipation: string
{
    case NOUVELLE_DATE_EFFET_ECHELON = 'new_step_effective_date';
    case CONSERVER_DATE_ORIGINE      = 'keep_origin_date';
    case AJUSTER_SELON_REGLE         = 'adjust_by_rule';

    public function libelle(): string
    {
        return match ($this) {
            self::NOUVELLE_DATE_EFFET_ECHELON => 'Nouvelle date d\'effet de l\'échelon',
            self::CONSERVER_DATE_ORIGINE      => 'Conserver la date d\'origine',
            self::AJUSTER_SELON_REGLE         => 'Ajuster selon la règle',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($e) => [$e->value => $e->libelle()])->all();
    }
}