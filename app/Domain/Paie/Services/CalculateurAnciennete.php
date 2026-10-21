<?php

namespace App\Domain\Paie\Services;

use App\Domain\Carriere\Models\SituationCarriere;
use App\Domain\Carriere\Services\CalculateurAncienneteUtile;
use App\Domain\Carriere\Services\ParametresAvancement;
use App\Domain\Classification\Models\PositionClassification;
use App\Domain\Interim\Models\InterimInterne;
use App\Domain\Interim\Services\RepertoireBareme;
use App\Domain\Paie\Models\RegleAnciennete;
use App\Domain\Personnel\Models\Salarie;
use Carbon\Carbon;

/**
 * Prime d'ancienneté (CCIT art. 40) : majoration du salaire minimum de base de la catégorie,
 * selon l'ancienneté utile du salarié. Taux, base et arrondi suivent la règle d'ancienneté
 * versionnée et les paramètres des avancements.
 */
class CalculateurAnciennete
{
    public function __construct(
        private CalculateurAncienneteUtile $anciennete,
        private ParametresAvancement $parametres,
        private RepertoireBareme $bareme,
    ) {}

    /**
     * Taux d'ancienneté applicable à un salarié à une date donnée.
     */
    public function calculerTaux(Salarie $salarie, Carbon $date): float
    {
        $regle = $this->regle($salarie, $date);
        if (! $regle) {
            return 0.0;
        }

        $this->parametres->pourEntreprise($salarie->entreprise_id);

        return $regle->calculerTaux($this->anciennete->annees($salarie, $date));
    }

    /**
     * Montant de la prime d'ancienneté pour le mois qui se termine à la date donnée.
     * $baseCalcul est le salaire de base réel, retenu lorsque la règle prévoit cette base.
     */
    public function calculerPrime(Salarie $salarie, float $baseCalcul, Carbon $date): float
    {
        $regle = $this->regle($salarie, $date);
        if (! $regle) {
            return 0.0;
        }

        $this->parametres->pourEntreprise($salarie->entreprise_id);
        $base = $this->base($salarie, $regle, $baseCalcul, $date);
        $tauxFin = $regle->calculerTaux($this->anciennete->annees($salarie, $date));

        if ($tauxFin <= 0) {
            return 0.0;
        }

        $montant = $base * $tauxFin / 100;

        // Mois où un seuil est franchi : prorata des jours avant et après, si le paramètre le prévoit.
        if ($this->parametres->get('prime_mois_seuil') === 'prorata_jours') {
            $debutMois = $date->copy()->startOfMonth();
            $tauxDebut = $regle->calculerTaux($this->anciennete->annees($salarie, $debutMois));
            if ($tauxDebut !== $tauxFin) {
                $corrigee = $this->anciennete->dateCorrigee($salarie, $date);
                $seuil = $corrigee->copy()->addYears($this->anciennete->annees($salarie, $date));
                $joursMois = $debutMois->daysInMonth;
                $joursAvant = max(0, min($joursMois, (int) $debutMois->diffInDays($seuil)));
                $montant = $base * ($tauxDebut * $joursAvant + $tauxFin * ($joursMois - $joursAvant)) / 100 / $joursMois;
            }
        }

        return $this->arrondir($montant);
    }

    /**
     * Base de la prime selon la règle : salaire de base réel (« salary_base »), minimum de
     * l'échelon du salarié (« echelon_salarie ») ou minimum de la catégorie (« echelon_a »).
     * Faute de position ou de barème, le salaire de base réel est retenu.
     */
    public function base(Salarie $salarie, RegleAnciennete $regle, float $salaireReel, Carbon $date): float
    {
        if (! in_array($regle->mode_base, ['echelon_a', 'echelon_salarie'], true)) {
            return $salaireReel;
        }

        // Pendant un intérim, base de la catégorie occupée si le paramètre le prévoit.
        if ($this->parametres->get('prime_base_pendant_interim') === 'categorie_occupee') {
            $interim = InterimInterne::withoutGlobalScopes()
                ->where('salarie_id', $salarie->id)
                ->where('date_debut', '<=', $date)
                ->where(fn ($q) => $q->where('date_fin_reelle', '>=', $date->copy()->startOfMonth())
                    ->orWhere(fn ($q) => $q->whereNull('date_fin_reelle')->where('date_fin_prevue', '>=', $date->copy()->startOfMonth())))
                ->with('categorieOccupee')
                ->first();
            if ($interim?->categorieOccupee) {
                try {
                    $minimum = $this->bareme->salaireMinimum($interim->categorieOccupee, $date);
                } catch (\DomainException) {
                    $minimum = null;
                }
                if ($minimum) {
                    return (float) $minimum;
                }
            }
        }

        $position = SituationCarriere::withoutGlobalScopes()->where('salarie_id', $salarie->id)->first()?->positionClassification;
        if (! $position) {
            return $salaireReel;
        }

        if ($regle->mode_base === 'echelon_salarie') {
            return $position->salaire_minimum !== null ? (float) $position->salaire_minimum : $salaireReel;
        }

        return $this->minimumCategorie($position) ?? $salaireReel;
    }

    /**
     * Minimum de la catégorie d'une position : position de même catégorie (et classe) à l'échelon
     * de référence, sinon la plus basse de la catégorie.
     */
    public function minimumCategorie(PositionClassification $position): ?float
    {
        $positions = PositionClassification::withoutGlobalScopes()
            ->where('referentiel_id', $position->referentiel_id)
            ->where('categorie_id', $position->categorie_id)
            ->when($position->classe_id, fn ($q) => $q->where('classe_id', $position->classe_id))
            ->whereNotNull('salaire_minimum')
            ->with('echelon')
            ->get();

        $reference = $positions->first(fn ($p) => $p->echelon?->code === $this->parametres->get('echelon_reference_prime'))
            ?? $positions->sortBy('salaire_minimum')->first();

        return $reference ? (float) $reference->salaire_minimum : null;
    }

    private function regle(Salarie $salarie, Carbon $date): ?RegleAnciennete
    {
        if (! $salarie->date_embauche) {
            return null;
        }

        // Règles versionnées : la plus récente en vigueur à la date s'applique.
        return RegleAnciennete::query()
            ->where('entreprise_id', $salarie->entreprise_id)
            ->enVigueur($date)
            ->orderByDesc('debut_effet')
            ->first();
    }

    private function arrondir(float $montant): float
    {
        $pas = max(1, (int) $this->parametres->get('arrondi_montants'));

        return (float) (round($montant / $pas) * $pas);
    }
}
