<?php

namespace App\Domain\Performance\Actions;

use App\Domain\Performance\Enums\StatutEntretien;
use App\Domain\Performance\Models\EntretienEvaluation;
use Illuminate\Support\Facades\DB;

class CreerEntretienEvaluation
{
    public function executer(array $donnees): EntretienEvaluation
    {
        return DB::transaction(function () use ($donnees) {
            // Éviter les doublons
            $existant = EntretienEvaluation::where('campagne_id', $donnees['campagne_id'])
                ->where('salarie_id', $donnees['salarie_id'])
                ->first();

            if ($existant) {
                throw new \DomainException('Un entretien existe déjà pour ce salarié dans cette campagne.');
            }

            $donnees['entreprise_id'] = auth()->user()->entreprise_id;
            $donnees['statut'] = StatutEntretien::A_PREPARER->value;
            $donnees['etat'] = 1;

            return EntretienEvaluation::create($donnees);
        });
    }
}