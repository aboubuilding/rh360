<?php

namespace App\Domain\Paie\Actions;

use App\Domain\Paie\Models\PeriodePaie;
use App\Domain\Paie\Services\GenerateurPeriode;

class OuvrirPeriode
{
    public function __construct(private GenerateurPeriode $generateur) {}

    public function executer(int $entrepriseId, int $annee, int $mois): PeriodePaie
    {
        return $this->generateur->ouvrir($entrepriseId, $annee, $mois);
    }

    public function executerProchaine(int $entrepriseId): PeriodePaie
    {
        return $this->generateur->ouvrirProchaine($entrepriseId);
    }
}