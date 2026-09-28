<?php

namespace App\Domain\Performance\Actions;

use App\Domain\Performance\Models\CritereEvaluation;

class CreerCritereEvaluation
{
    public function executer(array $donnees): CritereEvaluation
    {
        $donnees['entreprise_id'] = auth()->user()->entreprise_id;
        $donnees['etat'] = 1;

        return CritereEvaluation::create($donnees);
    }
}