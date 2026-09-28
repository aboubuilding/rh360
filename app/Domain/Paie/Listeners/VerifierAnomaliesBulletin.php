<?php

namespace App\Domain\Paie\Listeners;

use App\Domain\Paie\Events\BulletinCalcule;

class VerifierAnomaliesBulletin
{
    public function handle(BulletinCalcule $event): void
    {
        $bulletin = $event->bulletin;

        // Détecter les anomalies de calcul
        $anomalies = [];

        if ((float) $bulletin->montant_net < 0) {
            $anomalies[] = 'Net négatif';
        }
        if ((float) $bulletin->montant_brut === 0.0) {
            $anomalies[] = 'Brut nul';
        }
        if ((float) $bulletin->base_imposable > (float) $bulletin->brut_imposable) {
            $anomalies[] = 'Base imposable > brut imposable';
        }

        if (! empty($anomalies)) {
            \Log::warning("Anomalies bulletin {$bulletin->id} : " . implode(', ', $anomalies));
        }
    }
}