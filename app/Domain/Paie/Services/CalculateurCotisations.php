<?php

namespace App\Domain\Paie\Services;

use App\Domain\Paie\Models\RegleCotisation;
use Carbon\Carbon;

class CalculateurCotisations
{
    /**
     * Retourne la liste des cotisations applicables à une date donnée.
     *
     * @return array{lignes: array<int, array{code: string, nom: string, taux_salarial: float, montant: float}>, total_salarial: float, patronales: array<int, array>, total_patronal: float}
     */
    public function calculer(int $entrepriseId, float $base, Carbon $date): array
    {
        $regles = RegleCotisation::query()
            ->where('entreprise_id', $entrepriseId)
            ->enVigueur($date)
            ->orderBy('code')
            ->get();

        $resultats = [];
        $patronales = [];
        $total = 0.0;
        $totalPatronal = 0.0;

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

            // Part patronale : à la charge de l'employeur, jamais déduite du salaire (CCIT art. 35).
            $tauxPatronal = (float) $regle->taux_patronal;
            if ($tauxPatronal > 0) {
                $montantPatronal = round($base * ($tauxPatronal / 100), 2);
                $totalPatronal += $montantPatronal;
                $patronales[] = [
                    'code' => $regle->code.'-PAT',
                    'nom' => $regle->nom.' (part patronale)',
                    'taux_patronal' => $tauxPatronal,
                    'montant' => $montantPatronal,
                ];
            }
        }

        return [
            'lignes' => $resultats,
            'total_salarial' => round($total, 2),
            'patronales' => $patronales,
            'total_patronal' => round($totalPatronal, 2),
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