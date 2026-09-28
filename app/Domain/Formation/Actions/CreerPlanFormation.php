<?php

namespace App\Domain\Formation\Actions;

use App\Domain\Formation\Models\PlanFormation;
use Illuminate\Support\Facades\DB;

class CreerPlanFormation
{
    public function executer(array $donnees): PlanFormation
    {
        return DB::transaction(function () use ($donnees) {
            $donnees['entreprise_id'] = auth()->user()->entreprise_id;
            $donnees['statut'] = 'prevu';
            $donnees['etat'] = 1;

            return PlanFormation::create($donnees);
        });
    }
}