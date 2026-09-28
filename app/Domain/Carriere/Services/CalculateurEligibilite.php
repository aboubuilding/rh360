<?php

namespace App\Domain\Carriere\Services;

use App\Domain\Carriere\Models\SituationCarriere;
use App\Domain\Classification\Models\PositionClassification;
use App\Domain\Classification\Models\RegleEvolution;
use App\Domain\Personnel\Models\Salarie;
use Carbon\Carbon;

class CalculateurEligibilite
{
    /**
     * Calcule la prochaine échéance d'avancement d'un salarié.
     */
    public function calculer(Salarie $salarie): ?array
    {
        $situation = SituationCarriere::where('salarie_id', $salarie->id)->first();
        if (! $situation || ! $situation->position_classification_id) {
            return null;
        }

        $position = $situation->positionClassification;
        if (! $position || ! $position->position_suivante_id) {
            return null;
        }

        $regle = $this->chargerRegle($position);
        if (! $regle || ! $regle->mois_min) {
            return null;
        }

        $dateReference = $situation->date_reference_avancement
            ?? $situation->date_effet_echelon
            ?? $salarie->date_embauche;

        if (! $dateReference) return null;

        $dateEligibilite = Carbon::parse($dateReference)->addMonths($regle->mois_min);
        $joursRestants = now()->diffInDays($dateEligibilite, false);

        return [
            'salarie_id' => $salarie->id,
            'position_actuelle_id' => $position->id,
            'position_actuelle_libelle' => $position->libelleComplet(),
            'position_suivante_id' => $position->position_suivante_id,
            'date_reference' => $dateReference instanceof Carbon
                ? $dateReference->format('Y-m-d')
                : Carbon::parse($dateReference)->format('Y-m-d'),
            'delai_mois' => $regle->mois_min,
            'date_eligibilite' => $dateEligibilite->format('Y-m-d'),
            'jours_restants' => (int) $joursRestants,
            'est_eligible' => $joursRestants <= 0,
            'echeance_proche' => $joursRestants > 0 && $joursRestants <= 90,
            'anticipation_autorisee' => (bool) $regle->anticipation_autorisee,
        ];
    }

    /**
     * Liste des salariés dont l'avancement est échu ou à échéance < $jours.
     */
    public function echeancesProches(int $entrepriseId, int $jours = 90): array
    {
        $salaries = Salarie::where('entreprise_id', $entrepriseId)
            ->where('actif', true)
            ->get();

        $resultats = [];
        foreach ($salaries as $salarie) {
            $calcul = $this->calculer($salarie);
            if ($calcul && ($calcul['est_eligible'] || $calcul['echeance_proche'])) {
                $resultats[] = $calcul;
            }
        }

        // Trier par date d'éligibilité croissante
        usort($resultats, fn ($a, $b) => strcmp($a['date_eligibilite'], $b['date_eligibilite']));

        return $resultats;
    }

    private function chargerRegle(PositionClassification $position): ?RegleEvolution
    {
        return RegleEvolution::query()
            ->where('referentiel_id', $position->referentiel_id)
            ->where('type_evolution', 'echelon')
            ->where('actif', true)
            ->orderBy('priorite')
            ->first();
    }

    /**
     * Détermine si un salarié est éligible à une proposition d'avancement.
     */
    public function estEligible(Salarie $salarie): bool
    {
        $calcul = $this->calculer($salarie);
        return $calcul !== null && $calcul['est_eligible'];
    }

    /**
     * Retourne les salariés sans date calculable (pour alerte qualité données).
     */
    public function sansDateCalculable(int $entrepriseId): array
    {
        return Salarie::where('entreprise_id', $entrepriseId)
            ->where('actif', true)
            ->get()
            ->filter(function ($s) {
                $calcul = $this->calculer($s);
                return $calcul === null;
            })
            ->pluck('id')
            ->toArray();
    }
}