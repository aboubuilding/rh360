<?php

namespace App\Domain\Paie\Actions;

use App\Domain\Paie\Models\PeriodePaie;
use App\Domain\Paie\Services\MoteurPaie;

class CalculerPeriode
{
    public function __construct(private MoteurPaie $moteur) {}

    public function executer(PeriodePaie $periode): array
    {
        return $this->moteur->calculer($periode);
    }
}