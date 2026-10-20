<?php

namespace App\Domain\Contrats\Enums;

/**
 * RG-REM-04 — Motif de fin d'un CDD, qui conditionne l'indemnité de fin de contrat (Code du travail, art. 54, 55).
 */
enum MotifFinContrat: string
{
    case TERME = 'terme';
    case MOTIF_ECONOMIQUE = 'motif_economique';
    case AUTRE = 'autre';

    public function libelle(): string
    {
        return match ($this) {
            self::TERME => 'Arrivée du terme',
            self::MOTIF_ECONOMIQUE => 'Fin pour motif économique',
            self::AUTRE => 'Autre motif',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($e) => [$e->value => $e->libelle()])->all();
    }
}
