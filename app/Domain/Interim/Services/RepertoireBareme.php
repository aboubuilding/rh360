<?php

namespace App\Domain\Interim\Services;

use App\Domain\Classification\Models\CategorieClassification;
use App\Domain\Classification\Models\PositionClassification;
use App\Domain\Classification\Models\ReferentielClassification;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Salaire minimum d'une catégorie, lu dans le barème de la classification
 * (Annexe II CCIT 2026 et conventions sectorielles), versionné par date d'effet.
 */
class RepertoireBareme
{
    public function __construct(private ParametresInterim $parametres) {}

    /**
     * RG-INT-06 — Minimum de la catégorie à l'échelon de référence, à la date donnée.
     * Une convention sectorielle prime sur la CCIT si elle est plus favorable : on retient
     * le montant le plus élevé parmi les référentiels applicables portant une catégorie de même code.
     * Retourne null si aucun barème ne définit ce minimum, et lève une exception si un
     * référentiel définit plusieurs montants pour la même catégorie et le même échelon.
     */
    public function salaireMinimum(CategorieClassification $categorie, CarbonInterface $date): ?int
    {
        $echelon = (string) $this->parametres->get('echelon_reference');

        $positions = PositionClassification::query()
            ->whereIn('referentiel_id', $this->referentielsApplicables($categorie, $date))
            ->whereHas('categorie', fn ($q) => $q->where('code', $categorie->code))
            ->whereHas('echelon', fn ($q) => $q->where('code', $echelon))
            ->whereNotNull('salaire_minimum')
            ->where(fn ($q) => $q->whereNull('debut_effet')->orWhere('debut_effet', '<=', $date))
            ->where(fn ($q) => $q->whereNull('fin_effet')->orWhere('fin_effet', '>=', $date))
            ->get();

        foreach ($positions->groupBy('referentiel_id') as $parReferentiel) {
            if ($parReferentiel->pluck('salaire_minimum')->unique()->count() > 1) {
                throw new \DomainException(
                    "Barème ambigu : plusieurs salaires minimums pour la catégorie {$categorie->code}, "
                    ."échelon {$echelon} dans le même référentiel."
                );
            }
        }

        $minimum = $positions->max('salaire_minimum');

        return $minimum === null ? null : (int) $minimum;
    }

    /**
     * Référentiels du barème CCIT et des conventions sectorielles choisis dans les paramètres,
     * sinon ceux identifiés par leur niveau_source ; le référentiel de la catégorie elle-même
     * est toujours retenu. Seuls les référentiels en vigueur à la date sont pris en compte.
     *
     * @return Collection<int, int>
     */
    private function referentielsApplicables(CategorieClassification $categorie, CarbonInterface $date): Collection
    {
        $bareme = $this->parametres->get('referentiel_bareme_id');
        $conventions = (array) $this->parametres->get('referentiels_conventions_ids');

        return ReferentielClassification::query()
            ->where(function ($q) use ($categorie, $bareme, $conventions) {
                $q->where('id', $categorie->referentiel_id);

                if ($bareme) {
                    $q->orWhere('id', $bareme);
                } else {
                    $q->orWhere('niveau_source', $this->parametres->get('niveau_source_ccit'));
                }

                if ($conventions !== []) {
                    $q->orWhereIn('id', $conventions);
                } else {
                    $q->orWhere('niveau_source', $this->parametres->get('niveau_source_convention_sectorielle'));
                }
            })
            ->where(fn ($q) => $q->whereNull('debut_effet')->orWhere('debut_effet', '<=', $date))
            ->where(fn ($q) => $q->whereNull('fin_effet')->orWhere('fin_effet', '>=', $date))
            ->pluck('id');
    }
}
