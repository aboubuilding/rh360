<?php

namespace App\Domain\Paie\Enums;

enum RecurrenceRubrique: string
{
    case FIXE       = 'fixed';
    case VARIABLE   = 'variable';
    case PONCTUELLE = 'one_shot';

    public function libelle(): string
    {
        return match ($this) {
            self::FIXE       => 'Fixe (chaque mois)',
            self::VARIABLE   => 'Variable (saisie mensuelle)',
            self::PONCTUELLE => 'Ponctuelle',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($e) => [$e->value => $e->libelle()])->all();
    }
}