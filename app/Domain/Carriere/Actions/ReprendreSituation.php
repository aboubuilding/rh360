<?php

namespace App\Domain\Carriere\Actions;

use App\Domain\Carriere\Enums\StatutFiabilite;
use App\Domain\Carriere\Enums\StatutHistorique;
use App\Domain\Carriere\Enums\TypeSourceSituation;
use App\Domain\Carriere\Models\SituationCarriere;
use Illuminate\Support\Facades\DB;

class ReprendreSituation
{
    /**
     * Reconstruit une situation de carrière antérieure (reprise d'historique).
     */
    public function executer(int $salarieId, array $donnees): SituationCarriere
    {
        return DB::transaction(function () use ($salarieId, $donnees) {
            $situation = SituationCarriere::firstOrCreate(
                ['salarie_id' => $salarieId],
                [
                    'entreprise_id' => auth()->user()->entreprise_id,
                    'enregistre_le' => now(),
                    'etat' => 1,
                ]
            );

            $situation->update(array_merge($donnees, [
                'type_source' => TypeSourceSituation::REPRISE->value,
                'statut_historique' => $donnees['statut_historique'] ?? StatutHistorique::PARTIEL->value,
                'statut_fiabilite' => $donnees['statut_fiabilite'] ?? StatutFiabilite::A_CONFIRMER->value,
                'enregistre_par' => auth()->id(),
                'enregistre_le' => now(),
            ]));

            return $situation->fresh();
        });
    }
}