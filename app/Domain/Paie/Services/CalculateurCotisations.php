<?php

namespace App\Domain\Paie\Services;

use App\Domain\Paie\Models\RegleCotisation;
use Carbon\Carbon;

class CalculateurCotisations
{
    /**
     * Retourne la liste des cotisations applicables à une date donnée.
     *
     * @return array<int, array{code: string, nom: string, taux_salarial: float, montant: float}>
     */
    public function calculer(int $entrepriseId, float $base, Carbon $date): array
    {
        $regles = RegleCotisation::query()
            ->where('entreprise_id', $entrepriseId)
            ->enVigueur($date)
            ->orderBy('code')
            ->get();

        $resultats = [];
        $total = 0.0;

        foreach ($regles as $regle) {
            $taux = (float) $regle->taux_salarial;
            $montant = round($base * ($taux / 100), 2);
            $total += $montant;

            $resultats[] = [
                'code' => $regle->code,
                'nom' => $regle->nom,
                'taux_salarial' => $taux,
                'montant' => $montant,
            ];
        }

        return [
            'lignes' => $resultats,
            'total_salarial' => round($total, 2),
        ];
    }

    /**
     * Retourne uniquement les cotisations déductibles du brut imposable
     * (CNSS + AMU) — pour le calcul de l'IRPP.
     */
    public function cotisationsDeductibles(int $entrepriseId, float $base, Carbon $date): float
    {
        $codes = ['CNSS', 'AMU'];
        $regles = RegleCotisation::query()
            ->where('entreprise_id', $entrepriseId)
            ->whereIn('code', $codes)
            ->enVigueur($date)
            ->get();

        $total = 0.0;
        foreach ($regles as $regle) {
            $total += $base * ((float) $regle->taux_salarial / 100);
        }

        return round($total, 2);
    }
}