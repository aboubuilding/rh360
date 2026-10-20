<?php

namespace App\Domain\Interim\Services;

use App\Domain\Carriere\Models\SituationCarriere;
use App\Domain\Classification\Models\CategorieClassification;
use App\Domain\Classification\Models\PositionClassification;
use App\Domain\Personnel\Models\Salarie;

/**
 * RG-INT-01 — Compare la catégorie du poste occupé à celle du salarié.
 * Les catégories ne se comparent que dans un même référentiel, par leur ordre.
 */
class ComparateurCategories
{
    public function positionDuSalarie(Salarie $salarie): ?PositionClassification
    {
        return SituationCarriere::where('salarie_id', $salarie->id)->first()?->positionClassification;
    }

    /**
     * Catégorie du salarié exprimée dans le référentiel de la catégorie de référence :
     * directement si sa position en relève, sinon via sa position de conformité.
     */
    public function categorieDuSalarie(Salarie $salarie, CategorieClassification $reference): ?CategorieClassification
    {
        $position = $this->positionDuSalarie($salarie);
        if (! $position) {
            return null;
        }

        if ($position->referentiel_id === $reference->referentiel_id) {
            return $position->categorie;
        }

        $conformite = $position->positionConformite;
        if ($conformite && $conformite->referentiel_id === $reference->referentiel_id) {
            return $conformite->categorie;
        }

        return null;
    }

    public function estStrictementSuperieure(CategorieClassification $occupee, CategorieClassification $origine): bool
    {
        return $occupee->referentiel_id === $origine->referentiel_id
            && $occupee->ordre > $origine->ordre;
    }
}
