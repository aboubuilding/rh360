<?php

namespace App\Domain\Carriere\Services;

use App\Domain\Carriere\Models\SituationCarriere;
use App\Domain\Classification\Models\CategorieClassification;
use App\Domain\Classification\Models\PositionClassification;
use App\Domain\Classification\Models\RegleEvolution;
use App\Domain\Personnel\Models\Salarie;
use Carbon\Carbon;

class CalculateurEligibilite
{
    public function __construct(
        private ParametresAvancement $parametres,
        private CalculateurAncienneteUtile $anciennete,
    ) {}

    /**
     * Calcule la prochaine échéance d'avancement d'échelon d'un salarié (CCIT art. 33) :
     * date de référence + ancienneté maximale dans l'échelon + jours déduits de l'ancienneté.
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

        $this->parametres->pourEntreprise($salarie->entreprise_id);

        // Délai de la règle d'évolution du référentiel, sinon celui des paramètres.
        $regle = $this->chargerRegle($position);
        $delaiMois = $regle?->mois_min ?: (int) $this->parametres->get('delai_echelon_mois');
        if (! $delaiMois) {
            return null;
        }

        $dateReference = $situation->date_reference_avancement
            ?? $situation->date_effet_echelon
            ?? $salarie->date_embauche;

        if (! $dateReference) return null;

        $dateEligibilite = $this->echeance($salarie, Carbon::parse($dateReference), $delaiMois);
        $joursRestants = now()->startOfDay()->diffInDays($dateEligibilite, false);

        return [
            'salarie_id' => $salarie->id,
            'position_actuelle_id' => $position->id,
            'position_actuelle_libelle' => $position->libelleComplet(),
            'position_suivante_id' => $position->position_suivante_id,
            'date_reference' => $dateReference instanceof Carbon
                ? $dateReference->format('Y-m-d')
                : Carbon::parse($dateReference)->format('Y-m-d'),
            'delai_mois' => $delaiMois,
            'date_eligibilite' => $dateEligibilite->format('Y-m-d'),
            'jours_restants' => (int) $joursRestants,
            'est_eligible' => $joursRestants <= 0,
            'echeance_proche' => $joursRestants > 0 && $joursRestants <= 90,
            // Avancement au mérite avant l'échéance (CCIT art. 33) : autorisé par la règle d'évolution.
            'anticipation_autorisee' => (bool) ($regle?->anticipation_autorisee ?? true),
            'mode' => $this->parametres->get('avancement_echelon_mode'),
        ];
    }

    /**
     * Fenêtre d'avancement de catégorie d'un salarié au dernier échelon de sa catégorie (CCIT art. 33) :
     * au moins N et au plus M mois après une ancienneté donnée dans le dernier échelon.
     * Retourne null si le salarié n'est pas au dernier échelon.
     */
    public function fenetreCategorie(Salarie $salarie): ?array
    {
        $situation = SituationCarriere::where('salarie_id', $salarie->id)->first();
        $position = $situation?->positionClassification;
        if (! $position || $position->position_suivante_id) {
            return null;
        }

        $this->parametres->pourEntreprise($salarie->entreprise_id);

        $reference = $situation->date_effet_echelon ?? $situation->date_reference_avancement ?? $salarie->date_embauche;
        if (! $reference) {
            return null;
        }

        $finAttente = $this->echeance($salarie, Carbon::parse($reference), (int) $this->parametres->get('fenetre_categorie_attente_mois'));
        $ouverture = $finAttente->copy()->addMonthsNoOverflow((int) $this->parametres->get('fenetre_categorie_ouverture_mois'));
        $limite = $finAttente->copy()->addMonthsNoOverflow((int) $this->parametres->get('fenetre_categorie_limite_mois'));
        $aujourdhui = now()->startOfDay();

        // Catégorie immédiatement supérieure de la même classe ; à l'épuisement des catégories de la
        // classe, l'échelon hors catégorie (catégorie « HC… ») ou, à défaut, la fin de la grille.
        $categorieSuivante = $position->categorie
            ? CategorieClassification::where('referentiel_id', $position->referentiel_id)
                ->where('ordre', '>', $position->categorie->ordre)
                ->when($position->classe_id, fn ($q) => $q->whereHas('positions', fn ($p) => $p->where('classe_id', $position->classe_id)))
                ->orderBy('ordre')->first()
            : null;
        $horsCategorie = ! $categorieSuivante || str_starts_with($categorieSuivante->code, 'HC');

        return [
            'salarie_id' => $salarie->id,
            'salarie' => $salarie,
            'position_actuelle_libelle' => $position->libelleComplet(),
            'categorie_suivante' => $horsCategorie ? null : $categorieSuivante->libelle,
            'hors_categorie' => $horsCategorie,
            'date_reference' => Carbon::parse($reference)->format('Y-m-d'),
            'ouverture' => $ouverture->format('Y-m-d'),
            'limite' => $limite->format('Y-m-d'),
            'statut' => match (true) {
                $aujourdhui->lt($ouverture) => 'avant_ouverture',
                $aujourdhui->lte($limite) => 'ouverte',
                default => 'depassee',
            },
        ];
    }

    /**
     * Fenêtres de catégorie de l'entreprise, ouvertes ou s'ouvrant dans le délai donné.
     */
    public function fenetresCategorie(int $entrepriseId, int $jours = 90): array
    {
        $resultats = [];
        foreach (Salarie::where('entreprise_id', $entrepriseId)->where('actif', true)->get() as $salarie) {
            $fenetre = $this->fenetreCategorie($salarie);
            if ($fenetre && ($fenetre['statut'] !== 'avant_ouverture'
                || now()->startOfDay()->diffInDays(Carbon::parse($fenetre['ouverture']), false) <= $jours)) {
                $resultats[] = $fenetre;
            }
        }

        usort($resultats, fn ($a, $b) => strcmp($a['ouverture'], $b['ouverture']));

        return $resultats;
    }

    /**
     * Échéance = date de référence + durée en mois, décalée des jours déduits de l'ancienneté
     * pendant la période si les paramètres le prévoient.
     */
    private function echeance(Salarie $salarie, Carbon $reference, int $mois): Carbon
    {
        $echeance = $reference->copy()->startOfDay()->addMonthsNoOverflow($mois);

        if ($this->parametres->get('periodes_avancement') !== 'idem_prime') {
            return $echeance;
        }

        // Deux passes : les jours déduits peuvent eux-mêmes repousser l'échéance au-delà d'une absence.
        for ($i = 0; $i < 2; $i++) {
            $exclus = $this->anciennete->joursExclus($salarie, $reference, $echeance);
            $echeance = $reference->copy()->startOfDay()->addMonthsNoOverflow($mois)->addDays($exclus);
        }

        return $echeance;
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