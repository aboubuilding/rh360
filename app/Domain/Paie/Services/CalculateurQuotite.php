<?php

namespace App\Domain\Paie\Services;

use App\Domain\Paie\Models\ParametreLegalPaie;
use Carbon\CarbonInterface;

/**
 * Quotité saisissable et cessible d'un salaire (Code art. 174) : barème par tranches fixé par
 * décret, saisi comme paramètre externe daté « quotite_saisissable » :
 * [[plafond de la tranche, fraction], …, [null, fraction]].
 */
class CalculateurQuotite
{
    // Null si le barème n'est pas paramétré à la date.
    public function quotite(int $entrepriseId, float $assiette, CarbonInterface $date): ?float
    {
        $tranches = ParametreLegalPaie::valeurJson($entrepriseId, 'quotite_saisissable', $date);
        if (! $tranches) {
            return null;
        }

        $quotite = 0.0;
        $plancher = 0.0;
        foreach ($tranches as [$plafond, $fraction]) {
            $haut = $plafond === null ? $assiette : min($assiette, (float) $plafond);
            if ($haut > $plancher) {
                $quotite += ($haut - $plancher) * (float) $fraction;
            }
            if ($plafond === null || $assiette <= (float) $plafond) {
                break;
            }
            $plancher = (float) $plafond;
        }

        // Arrondi à l'entier inférieur : la retenue ne dépasse jamais la quotité.
        return floor(max(0, $quotite));
    }
}
