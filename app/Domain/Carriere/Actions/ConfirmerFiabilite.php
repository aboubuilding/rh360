<?php

namespace App\Domain\Carriere\Actions;

use App\Domain\Carriere\Enums\StatutFiabilite;
use App\Domain\Carriere\Enums\StatutHistorique;
use App\Domain\Carriere\Models\SituationCarriere;

class ConfirmerFiabilite
{
    public function executer(SituationCarriere $situation, ?string $note = null): SituationCarriere
    {
        $situation->update([
            'statut_fiabilite' => StatutFiabilite::CONFIRME->value,
            'statut_historique' => StatutHistorique::COMPLET->value,
            'observations' => trim(($situation->observations ?? '') . "\n" . ($note ?? '')),
        ]);

        return $situation->fresh();
    }
}