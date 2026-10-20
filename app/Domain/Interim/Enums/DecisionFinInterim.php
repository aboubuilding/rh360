<?php

namespace App\Domain\Interim\Enums;

/**
 * RG-INT-08 — Décision obligatoire à l'expiration des délais.
 */
enum DecisionFinInterim: string
{
    case RECLASSEMENT = 'reclassement';
    case RETOUR = 'retour';

    public function libelle(): string
    {
        return match ($this) {
            self::RECLASSEMENT => 'Reclassement dans la catégorie occupée',
            self::RETOUR => 'Retour à l\'emploi et à la situation d\'origine',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($e) => [$e->value => $e->libelle()])->all();
    }
}
