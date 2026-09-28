<?php

namespace App\Domain\Paie\Services;

use App\Domain\Paie\Models\RegleIrpp;
use App\Domain\Personnel\Models\Salarie;
use Carbon\Carbon;

class CalculateurIRPP
{
    /**
     * Calcule l'IRPP d'un salarié pour une période.
     *
     * @return array{
     *     brut_imposable: float,
     *     cotisations_deductibles: float,
     *     abattement_professionnel: float,
     *     deduction_charges: float,
     *     base_imposable: float,
     *     montant_irpp: float
     * }
     */
    public function calculer(
        Salarie $salarie,
        float $brutImposable,
        float $cotisationsDeductibles,
        Carbon $date,
    ): array {
        $regle = RegleIrpp::query()
            ->where('entreprise_id', $salarie->entreprise_id)
            ->enVigueur($date)
            ->first();

        if (! $regle) {
            return [
                'brut_imposable' => $brutImposable,
                'cotisations_deductibles' => $cotisationsDeductibles,
                'abattement_professionnel' => 0,
                'deduction_charges' => 0,
                'base_imposable' => $brutImposable,
                'montant_irpp' => 0,
            ];
        }

        // 1. Abattement professionnel sur le brut imposable
        $abattement = $regle->calculerAbattement($brutImposable);

        // 2. Nombre de charges de famille
        $nbCharges = $this->compterCharges($salarie);
        $deductionCharges = $regle->calculerDeductionCharges($nbCharges);

        // 3. Base imposable = brut imposable - cotisations - abattement - charges
        $baseImposable = $brutImposable - $cotisationsDeductibles - $abattement - $deductionCharges;
        $baseImposable = max(0, round($baseImposable, 2));

        // 4. IRPP progressif
        $irpp = $regle->calculerIrpp($baseImposable);

        return [
            'brut_imposable' => round($brutImposable, 2),
            'cotisations_deductibles' => round($cotisationsDeductibles, 2),
            'abattement_professionnel' => $abattement,
            'deduction_charges' => $deductionCharges,
            'base_imposable' => $baseImposable,
            'montant_irpp' => $irpp,
        ];
    }

    /**
     * Compte les personnes à charge au foyer du salarié.
     */
    private function compterCharges(Salarie $salarie): int
    {
        return $salarie->membresFoyer()
            ->where('est_a_charge', true)
            ->where('actif', true)
            ->where(function ($q) {
                // Exclure les charges terminées
                $q->whereNull('date_fin_charge')
                  ->orWhere('date_fin_charge', '>=', now());
            })
            ->count();
    }
}