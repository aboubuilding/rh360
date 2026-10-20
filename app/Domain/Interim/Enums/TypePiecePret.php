<?php

namespace App\Domain\Interim\Enums;

/**
 * RG-PMO-02 — les trois pièces obligatoires avant le début du prêt.
 */
enum TypePiecePret: string
{
    case ACCORD_SALARIE = 'accord_salarie';
    case CONVENTION = 'convention';
    case AVENANT = 'avenant';

    public function libelle(): string
    {
        return match ($this) {
            self::ACCORD_SALARIE => 'Accord écrit du salarié',
            self::CONVENTION => 'Convention de mise à disposition (durée, identité et qualification du salarié, mode de calcul de la refacturation)',
            self::AVENANT => 'Avenant au contrat signé (travail confié, horaires, lieu, caractéristiques du poste)',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($e) => [$e->value => $e->libelle()])->all();
    }
}
