<?php

namespace App\Domain\Interim\Services;

use App\Domain\Classification\Models\CategorieClassification;
use App\Domain\Classification\Models\PositionClassification;
use App\Domain\Classification\Models\ReferentielClassification;
use Carbon\CarbonInterface;

/**
 * Salaire minimum d'une catégorie, lu dans le barème de la classification
 * (Annexe II CCIT 2026 et conventions sectorielles), versionné par date d'effet.
 */
class RepertoireBareme
{
    /**
     * Minimum de la catégorie à l'échelon de référence (« A »), à la date donnée.
     * Une convention sectorielle prime sur la CCIT si elle est plus favorable :
     * on retient le montant le plus élevé parmi les référentiels applicables
     * qui portent une catégorie de même code.
     * Retourne null si aucun barème ne définit ce minimum, et lève une exception
     * si un référentiel définit plusieurs montants pour la même catégorie et le même échelon.
     */
    public function salaireMinimum(CategorieClassification $categorie, CarbonInterface $date): ?int
    {
        $niveaux = [
            config('interim.bareme.niveau_source_ccit'),
            config('interim.bareme.niveau_source_convention_sectorielle'),
        ];

        $referentielIds = ReferentielClassification::query()
            ->where(fn ($q) => $q->whereIn('niveau_source', $niveaux)->orWhere('id', $categorie->referentiel_id))
            ->where(fn ($q) => $q->whereNull('debut_effet')->orWhere('debut_effet', '<=', $date))
            ->where(fn ($q) => $q->whereNull('fin_effet')->orWhere('fin_effet', '>=', $date))
            ->pluck('id');

        $positions = PositionClassification::query()
            ->whereIn('referentiel_id', $referentielIds)
            ->whereHas('categorie', fn ($q) => $q->where('code', $categorie->code))
            ->whereHas('echelon', fn ($q) => $q->where('code', config('interim.bareme.echelon_reference')))
            ->whereNotNull('salaire_minimum')
            ->where(fn ($q) => $q->whereNull('debut_effet')->orWhere('debut_effet', '<=', $date))
            ->where(fn ($q) => $q->whereNull('fin_effet')->orWhere('fin_effet', '>=', $date))
            ->get();

        foreach ($positions->groupBy('referentiel_id') as $parReferentiel) {
            if ($parReferentiel->pluck('salaire_minimum')->unique()->count() > 1) {
                throw new \DomainException(
                    "Barème ambigu : plusieurs salaires minimums pour la catégorie {$categorie->code}, "
                    .'échelon '.config('interim.bareme.echelon_reference').' dans le même référentiel.'
                );
            }
        }

        $minimum = $positions->max('salaire_minimum');

        return $minimum === null ? null : (int) $minimum;
    }
}
