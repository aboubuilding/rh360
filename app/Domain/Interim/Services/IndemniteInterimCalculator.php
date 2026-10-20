<?php

namespace App\Domain\Interim\Services;

use App\Domain\Interim\Models\InterimInterne;
use App\Domain\Paie\Models\ElementPaieSalarie;
use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * RG-INT-06 / RG-INT-10 — Indemnité d'intérim d'un emploi supérieur (CCIT art. 13).
 */
class IndemniteInterimCalculator
{
    public function __construct(
        private CalculateurDelaisInterim $delais,
        private RepertoireBareme $bareme,
        private ParametresInterim $parametres,
    ) {}

    /**
     * RG-INT-06 — Premier jour du 3e mois : le lendemain de l'échéance des deux mois
     * (intérims successifs cumulés selon le paramètre). Les deux premiers mois, la paie
     * reste celle de la catégorie d'origine (RG-INT-02).
     */
    public function debutIndemnite(InterimInterne $interim): Carbon
    {
        $dejaEffectues = $this->delais->joursDejaEffectues(
            $interim->poste_occupe_id, $interim->titulaire_id, $interim->date_debut, $interim->id,
        );

        return $this->delais->plafondNormal($interim->date_debut, $dejaEffectues)->addDay();
    }

    // RG-INT-08 / RG-INT-10 — L'indemnité prend fin avec l'intérim.
    public function finIndemnite(InterimInterne $interim): Carbon
    {
        return ($interim->date_fin_reelle ?? $interim->date_fin_prevue)->copy()->startOfDay();
    }

    public function salaireBase(InterimInterne $interim): int
    {
        return (int) ElementPaieSalarie::where('salarie_id', $interim->salarie_id)
            ->where('actif', true)
            ->whereHas('rubrique', fn ($q) => $q->where('code', 'SAL-BASE'))
            ->value('montant');
    }

    /**
     * RG-INT-06 — indemnite_mensuelle = max(0, salaire_minimum(categorie_occupee, échelon de référence) − salaire_base(salarie)).
     *
     * @throws IndemniteIncalculable si le barème ne définit pas, ou pas sans ambiguïté, le minimum.
     */
    public function montantMensuel(InterimInterne $interim, CarbonInterface $date): int
    {
        $minimum = $this->minimumCategorie($interim, $date);

        return max(0, $minimum - $this->salaireBase($interim));
    }

    public function minimumCategorie(InterimInterne $interim, CarbonInterface $date): int
    {
        try {
            $minimum = $this->bareme->salaireMinimum($interim->categorieOccupee, $date);
        } catch (\DomainException $e) {
            throw new IndemniteIncalculable($e->getMessage());
        }

        if ($minimum === null) {
            throw new IndemniteIncalculable(
                "Barème introuvable : aucun salaire minimum pour la catégorie {$interim->categorieOccupee?->code}, "
                .'échelon '.$this->parametres->get('echelon_reference').' (CCIT 2026, Annexe II).'
            );
        }

        return $minimum;
    }

    /**
     * Indemnité due pour un mois de paie, proratisée sur les jours couverts (RG-INT-10).
     * Le salaire de base est celui du salarié, ou celui du bulletin du mois pour un contrôle
     * a posteriori. Retourne null si l'indemnité ne court pas pendant ce mois.
     */
    public function pourMois(InterimInterne $interim, int $annee, int $mois, ?int $salaireBase = null): ?IndemniteMois
    {
        $debutMois = Carbon::create($annee, $mois, 1)->startOfDay();
        $finMois = $debutMois->copy()->endOfMonth()->startOfDay();

        $du = $this->debutIndemnite($interim)->max($debutMois);
        $au = $this->finIndemnite($interim)->min($finMois);

        if ($du->gt($au)) {
            return null;
        }

        $minimum = $this->minimumCategorie($interim, $du);
        $salaireBase ??= $this->salaireBase($interim);
        $mensuel = max(0, $minimum - $salaireBase);
        [$jours, $joursBase] = $this->joursProrata($du, $au, $debutMois, $finMois);

        return new IndemniteMois(
            montant: (int) round($mensuel * $jours / $joursBase),
            mensuel: $mensuel,
            minimum: $minimum,
            salaireBase: $salaireBase,
            du: $du,
            au: $au,
            jours: $jours,
            joursBase: $joursBase,
        );
    }

    /**
     * Proratisation selon le paramètre : jours réels du mois, ou base 30.
     *
     * @return array{0:int, 1:int}
     */
    public function joursProrata(Carbon $du, Carbon $au, Carbon $debutMois, Carbon $finMois): array
    {
        $jours = (int) $du->diffInDays($au) + 1;

        if ($this->parametres->get('prorata_indemnite') === 'base_30') {
            $moisComplet = $du->eq($debutMois) && $au->eq($finMois);

            return [$moisComplet ? 30 : min(30, $jours), 30];
        }

        return [$jours, $debutMois->daysInMonth];
    }
}
