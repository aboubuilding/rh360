<?php

namespace App\Domain\Performance\Actions;

use App\Domain\Performance\Enums\StatutObjectif;
use App\Domain\Performance\Models\ObjectifEvaluation;

class CreerObjectifEvaluation
{
    public function executer(array $donnees): ObjectifEvaluation
    {
        $donnees['entreprise_id'] = auth()->user()->entreprise_id;
        $donnees['statut'] = StatutObjectif::A_REALISER->value;
        $donnees['etat'] = 1;

        return ObjectifEvaluation::create($donnees);
    }
}