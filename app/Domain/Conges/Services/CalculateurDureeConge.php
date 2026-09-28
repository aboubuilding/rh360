<?php

namespace App\Domain\Conges\Services;

use App\Domain\Conges\Enums\UniteConge;
use App\Domain\Conges\Models\TypeConge;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class CalculateurDureeConge
{
    /**
     * Calcule la durée d'un congé selon son unité.
     */
    public function calculer(Carbon $debut, Carbon $reprise, TypeConge $type): float
    {
        return match ($type->unite) {
            UniteConge::JOUR_CALENDAIRE => $this->joursCalendaires($debut, $reprise),
            UniteConge::JOUR_OUVRABLE   => $this->joursOuvrables($debut, $reprise),
            UniteConge::HEURE           => 0, // Saisi manuellement
        };
    }

    /**
     * Jours calendaires entre début et reprise (reprise exclue).
     */
    public function joursCalendaires(Carbon $debut, Carbon $reprise): float
    {
        return max(0, $debut->diffInDays($reprise));
    }

    /**
     * Jours ouvrables (lundi-vendredi, hors week-end).
     */
    public function joursOuvrables(Carbon $debut, Carbon $reprise): float
    {
        $count = 0;
        foreach (CarbonPeriod::create($debut, $reprise->copy()->subDay()) as $jour) {
            if (! $jour->isWeekend()) {
                $count++;
            }
        }
        return (float) $count;
    }

    /**
     * Date de reprise calculée depuis la date de début + durée.
     */
    public function calculerDateReprise(Carbon $debut, float $duree, TypeConge $type): Carbon
    {
        return match ($type->unite) {
            UniteConge::JOUR_CALENDAIRE => $debut->copy()->addDays((int) $duree),
            UniteConge::JOUR_OUVRABLE   => $this->ajouterJoursOuvrables($debut, (int) $duree),
            UniteConge::HEURE           => $debut->copy()->addDay(),
        };
    }

    private function ajouterJoursOuvrables(Carbon $date, int $jours): Carbon
    {
        $ajoutes = 0;
        $courant = $date->copy();
        while ($ajoutes < $jours) {
            $courant->addDay();
            if (! $courant->isWeekend()) {
                $ajoutes++;
            }
        }
        return $courant;
    }
}