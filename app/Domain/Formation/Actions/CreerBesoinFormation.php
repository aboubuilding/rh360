<?php

namespace App\Domain\Formation\Actions;

use App\Domain\Formation\Enums\PrioriteBesoinFormation;
use App\Domain\Formation\Enums\StatutBesoinFormation;
use App\Domain\Formation\Models\BesoinFormation;
use Illuminate\Support\Facades\DB;

class CreerBesoinFormation
{
    public function executer(array $donnees): BesoinFormation
    {
        return DB::transaction(function () use ($donnees) {
            $donnees['entreprise_id'] = auth()->user()->entreprise_id;
            $donnees['priorite'] = $donnees['priorite'] ?? PrioriteBesoinFormation::NORMALE->value;
            $donnees['statut'] = StatutBesoinFormation::A_ETUDIER->value;
            $donnees['cree_par'] = auth()->id();
            $donnees['etat'] = 1;

            return BesoinFormation::create($donnees);
        });
    }
}