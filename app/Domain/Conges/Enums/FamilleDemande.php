<?php

namespace App\Domain\Conges\Enums;

/**
 * Famille d'un type de demande : elle regroupe les règles de gestion applicables.
 */
enum FamilleDemande: string
{
    case CONGE_PAYE = 'conge_paye';
    case PERMISSION = 'permission';
    case FAMILLE    = 'famille';
    case MATERNITE  = 'maternite';
    case MALADIE    = 'maladie';
    case SANS_SOLDE = 'sans_solde';
    case SYNDICAL   = 'syndical';
    case AUTRE      = 'autre';

    public function libelle(): string
    {
        return match ($this) {
            self::CONGE_PAYE => 'Congé payé',
            self::PERMISSION => 'Permission exceptionnelle',
            self::FAMILLE    => 'Enfant malade et veuvage',
            self::MATERNITE  => 'Maternité et allaitement',
            self::MALADIE    => 'Maladie',
            self::SANS_SOLDE => 'Congé sans solde',
            self::SYNDICAL   => 'Activités syndicales et représentation',
            self::AUTRE      => 'Autre',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($e) => [$e->value => $e->libelle()])->all();
    }
}
