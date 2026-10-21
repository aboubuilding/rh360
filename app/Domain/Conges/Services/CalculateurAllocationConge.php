<?php

namespace App\Domain\Conges\Services;

use App\Domain\Conges\Models\DemandeConge;
use App\Domain\Paie\Models\LigneBulletin;
use App\Domain\Paie\Models\PeriodePaie;

/**
 * Allocation de congé payé : le douzième de la rémunération perçue pendant les 12 mois
 * précédant le départ, primes de rendement et remboursements de frais exclus (Code art. 202 ;
 * CCIT art. 49 c), au prorata des jours pris.
 *
 * Le douzième couvre le droit acquis sur la période (2,5 jours par mois) : avec n mois de
 * bulletins, le jour d'allocation vaut rémunération / (12 × 2,5 × n), soit rémunération / 360
 * sur 12 mois.
 */
class CalculateurAllocationConge
{
    public function __construct(private ParametresDemandes $parametres) {}

    /**
     * @return array{remuneration: int, mois: int, allocation_periode: int, par_jour: float, jours: float, montant: int, exclues: array<int, string>}|null
     */
    public function calculer(DemandeConge $demande): ?array
    {
        if (! GrandLivreConges::gere($demande->typeConge) || ! $demande->date_debut) {
            return null;
        }

        $exclues = $this->rubriquesExclues($demande->entreprise_id);
        $debut = $demande->date_debut->copy()->startOfMonth()->subMonthsNoOverflow(12);
        $fin = $demande->date_debut->copy()->startOfMonth()->subMonthNoOverflow();

        $periodes = PeriodePaie::pourEntreprise($demande->entreprise_id)
            ->whereRaw('(annee * 100 + mois) between ? and ?', [$debut->year * 100 + $debut->month, $fin->year * 100 + $fin->month])
            ->pluck('id');

        $lignes = LigneBulletin::withoutGlobalScopes()
            ->join('bulletins_paie', 'bulletins_paie.id', '=', 'lignes_bulletins.bulletin_id')
            ->where('lignes_bulletins.entreprise_id', $demande->entreprise_id)
            ->where('bulletins_paie.salarie_id', $demande->salarie_id)
            ->whereIn('bulletins_paie.periode_id', $periodes)
            ->where('lignes_bulletins.nature', 'gain')
            ->whereNotIn('lignes_bulletins.code', $exclues)
            ->select('lignes_bulletins.montant', 'bulletins_paie.periode_id');

        $remuneration = (float) (clone $lignes)->sum('lignes_bulletins.montant');
        $mois = (int) (clone $lignes)->distinct()->count('bulletins_paie.periode_id');
        if ($mois === 0) {
            return null;
        }

        $droitMensuel = (float) $demande->typeConge->droit_annuel / 12;
        $parJour = $remuneration / (12 * $droitMensuel * $mois);
        $jours = (float) $demande->duree_jours;

        return [
            'remuneration' => (int) round($remuneration),
            'mois' => $mois,
            'allocation_periode' => (int) round($remuneration / 12),
            'par_jour' => round($parJour, 2),
            'jours' => $jours,
            'montant' => (int) round($parJour * $jours),
            'exclues' => $exclues,
        ];
    }

    /** @return array<int, string> codes des rubriques exclues de la base */
    public function rubriquesExclues(int $entrepriseId): array
    {
        $valeur = (string) $this->parametres->pourEntreprise($entrepriseId)->get('conge_allocation_rubriques_exclues');

        return array_values(array_filter(array_map(fn ($c) => strtoupper(trim($c)), explode(',', $valeur))));
    }
}
