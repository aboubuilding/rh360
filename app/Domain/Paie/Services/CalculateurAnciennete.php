<?php

namespace App\Domain\Paie\Services;

use App\Domain\Paie\Models\RegleAnciennete;
use App\Domain\Personnel\Models\Salarie;
use Carbon\Carbon;

class CalculateurAnciennete
{
    /**
     * Calcule le taux d'ancienneté applicable à un salarié à une date donnée.
     */
    public function calculerTaux(Salarie $salarie, Carbon $date): float
    {
        if (! $salarie->date_embauche) {
            return 0.0;
        }

        $regle = RegleAnciennete::query()
            ->where('entreprise_id', $salarie->entreprise_id)
            ->enVigueur($date)
            ->first();

        if (! $regle) {
            return 0.0;
        }

        // Années de service complètes à la date
        $anneesService = $salarie->date_embauche->diffInYears($date);

        return $regle->calculerTaux((int) $anneesService);
    }

    /**
     * Calcule le montant de la prime d'ancienneté sur la base donnée.
     */
    public function calculerPrime(Salarie $salarie, float $baseCalcul, Carbon $date): float
    {
        $taux = $this->calculerTaux($salarie, $date);
        if ($taux <= 0) return 0.0;

        return round($baseCalcul * ($taux / 100), 2);
    }
}