<?php

namespace App\Domain\Paie\Enums;

enum ModeCalculRubrique: string
{
    case MONTANT_FIXE   = 'amount';
    case TAUX_POURCENT  = 'rate';
    case FORMULE        = 'formula';
    case QUANTITE_TAUX  = 'quantity_rate';

    public function libelle(): string
    {
        return match ($this) {
            self::MONTANT_FIXE  => 'Montant fixe',
            self::TAUX_POURCENT => 'Taux (%)',
            self::FORMULE       => 'Formule',
            self::QUANTITE_TAUX => 'Quantité × Taux',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($e) => [$e->value => $e->libelle()])->all();
    }
}