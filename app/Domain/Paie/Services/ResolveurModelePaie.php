<?php

namespace App\Domain\Paie\Services;

use App\Domain\Carriere\Models\SituationCarriere;
use App\Domain\Classification\Models\PositionClassification;
use App\Domain\Paie\Models\AffectationModelePaie;
use App\Domain\Paie\Models\ModelePaie;
use App\Domain\Personnel\Models\Salarie;
use Carbon\CarbonInterface;

/**
 * Modèle de bulletin d'un salarié à une date : l'association manuelle en vigueur, sinon le
 * modèle actif de la catégorie du salarié. Un modèle d'une autre classe que celle du salarié
 * (un cadre sur un modèle d'agent d'exécution) est refusé.
 */
class ResolveurModelePaie
{
    /**
     * @return array{modele: ?ModelePaie, mode: ?string, anomalie: ?string}
     */
    public function pour(Salarie $salarie, CarbonInterface $date): array
    {
        $position = SituationCarriere::withoutGlobalScopes()->where('salarie_id', $salarie->id)->first()?->positionClassification;

        $affectation = AffectationModelePaie::pourEntreprise($salarie->entreprise_id)
            ->where('salarie_id', $salarie->id)
            ->where('debut', '<=', $date->toDateString())
            ->where(fn ($q) => $q->whereNull('fin')->orWhere('fin', '>=', $date->toDateString()))
            ->orderByDesc('debut')
            ->with('modele')
            ->first();

        if ($affectation?->modele) {
            $modele = $affectation->modele;
            if (! $modele->actif) {
                return ['modele' => null, 'mode' => 'manuel', 'anomalie' => "Le modèle « {$modele->nom} » associé au salarié est inactif."];
            }
            $classeModele = $this->classe($modele->categorie_id, $position?->referentiel_id);
            if ($position?->classe_id && $classeModele && $classeModele !== $position->classe_id) {
                return ['modele' => null, 'mode' => 'manuel', 'anomalie' => "Le modèle « {$modele->nom} » est prévu pour une autre classe que celle du salarié."];
            }

            return ['modele' => $modele, 'mode' => 'manuel', 'anomalie' => null];
        }

        if (! $position?->categorie_id) {
            return ['modele' => null, 'mode' => null, 'anomalie' => 'Aucun modèle de bulletin : la catégorie du salarié n\'est pas renseignée.'];
        }

        $modele = ModelePaie::pourEntreprise($salarie->entreprise_id)
            ->where('categorie_id', $position->categorie_id)
            ->where('actif', true)
            ->first();

        return $modele
            ? ['modele' => $modele, 'mode' => 'automatique', 'anomalie' => null]
            : ['modele' => null, 'mode' => null, 'anomalie' => 'Aucun modèle de bulletin actif pour la catégorie du salarié.'];
    }

    // Classe d'une catégorie, déduite des positions du barème.
    private function classe(?int $categorieId, ?int $referentielId): ?int
    {
        if (! $categorieId) {
            return null;
        }

        return PositionClassification::withoutGlobalScopes()
            ->where('categorie_id', $categorieId)
            ->when($referentielId, fn ($q) => $q->where('referentiel_id', $referentielId))
            ->whereNotNull('classe_id')
            ->value('classe_id');
    }
}
