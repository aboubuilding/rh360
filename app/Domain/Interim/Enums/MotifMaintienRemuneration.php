<?php

namespace App\Domain\Interim\Enums;

/**
 * RG-INF-01 à 03 — Situations où le salaire (et le classement) sont maintenus.
 */
enum MotifMaintienRemuneration: string
{
    case AFFECTATION_PROVISOIRE_INFERIEURE = 'affectation_provisoire_inferieure';
    case AFFECTATION_DEFINITIVE_INFERIEURE = 'affectation_definitive_inferieure';
    case MUTATION_ETAT_GROSSESSE = 'mutation_etat_grossesse';

    public function libelle(): string
    {
        return match ($this) {
            self::AFFECTATION_PROVISOIRE_INFERIEURE => 'Affectation provisoire à un emploi inférieur',
            self::AFFECTATION_DEFINITIVE_INFERIEURE => 'Affectation définitive à un poste inférieur (nécessité de service)',
            self::MUTATION_ETAT_GROSSESSE => 'Mutation d\'une salariée enceinte en raison de son état',
        };
    }

    // Le classement est maintenu dans les deux cas d'affectation à un emploi inférieur.
    public function maintientClassement(): bool
    {
        return $this !== self::MUTATION_ETAT_GROSSESSE;
    }
}
