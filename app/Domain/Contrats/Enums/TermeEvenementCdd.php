<?php

namespace App\Domain\Contrats\Enums;

/**
 * RG-REM-02 — Terme d'un CDD de remplacement fixé par un événement (Code du travail, art. 48, 49).
 */
enum TermeEvenementCdd: string
{
    case RETOUR_TITULAIRE = 'retour_titulaire';
    case RUPTURE_CONTRAT_TITULAIRE = 'rupture_contrat_titulaire';

    public function libelle(): string
    {
        return match ($this) {
            self::RETOUR_TITULAIRE => 'Retour du titulaire',
            self::RUPTURE_CONTRAT_TITULAIRE => 'Rupture du contrat du titulaire',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($e) => [$e->value => $e->libelle()])->all();
    }
}
