<?php

namespace App\Domain\Paie\Services;

use App\Domain\Paie\Models\HeureSupplementaire;
use Carbon\Carbon;

class CalculateurHeuresSupp
{
    /**
     * Calcule le taux horaire à partir du salaire de base mensuel.
     * Base légale : 173,33 heures par mois.
     */
    public function calculerTauxHoraire(float $salaireBaseMensuel): float
    {
        return round($salaireBaseMensuel / 173.33, 4);
    }

    /**
     * Crée un acte d'heures supplémentaires avec les taux figés.
     */
    public function creer(int $entrepriseId, int $salarieId, array $donnees, float $salaireBaseMensuel): HeureSupplementaire
    {
        $tauxHoraire = $this->calculerTauxHoraire($salaireBaseMensuel);

        return HeureSupplementaire::create(array_merge($donnees, [
            'entreprise_id' => $entrepriseId,
            'salarie_id' => $salarieId,
            'salaire_base_fige' => $salaireBaseMensuel,
            'taux_horaire' => $tauxHoraire,
            'cree_par' => auth()->id(),
            'etat' => 1,
        ]));
    }

    /**
     * Calcule le montant total pour une période donnée (toutes HS confondues).
     */
    public function totalPeriode(int $entrepriseId, int $periodeId): float
    {
        $actes = HeureSupplementaire::where('entreprise_id', $entrepriseId)
            ->pourPeriode($periodeId)
            ->get();

        return round($actes->sum(fn ($h) => $h->montantTotal()), 2);
    }

    /**
     * Retourne le détail par taux pour un acte.
     */
    public function detailActe(HeureSupplementaire $acte): array
    {
        $taux = (float) $acte->taux_horaire;

        return [
            ['libelle' => 'HS 20 %', 'heures' => (float) $acte->heures_hs20, 'montant' => round($acte->heures_hs20 * $taux * 1.20, 2)],
            ['libelle' => 'HS 40 %', 'heures' => (float) $acte->heures_hs40, 'montant' => round($acte->heures_hs40 * $taux * 1.40, 2)],
            ['libelle' => 'HS 65 % jour', 'heures' => (float) $acte->heures_hs65_jour, 'montant' => round($acte->heures_hs65_jour * $taux * 1.65, 2)],
            ['libelle' => 'HS 65 % nuit', 'heures' => (float) $acte->heures_hs65_nuit, 'montant' => round($acte->heures_hs65_nuit * $taux * 1.65, 2)],
            ['libelle' => 'HS 100 %', 'heures' => (float) $acte->heures_hs100, 'montant' => round($acte->heures_hs100 * $taux * 2.00, 2)],
        ];
    }
}