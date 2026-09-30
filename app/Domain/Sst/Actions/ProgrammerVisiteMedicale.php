<?php

namespace App\Domain\Sst\Actions;

use App\Domain\Sst\Enums\AptitudeMedicale;
use App\Domain\Sst\Enums\StatutVisiteMedicale;
use App\Domain\Sst\Models\VisiteMedicale;
use Illuminate\Support\Facades\DB;

class ProgrammerVisiteMedicale
{
    public function executer(array $donnees): VisiteMedicale
    {
        return DB::transaction(function () use ($donnees) {
            $donnees['entreprise_id'] = auth()->user()->entreprise_id;
            $donnees['statut'] = StatutVisiteMedicale::PLANIFIEE->value;
            $donnees['aptitude'] = AptitudeMedicale::EN_ATTENTE->value;
            $donnees['cree_par'] = auth()->id();
            $donnees['modifie_par'] = auth()->id();
            $donnees['revision'] = 1;
            $donnees['etat'] = 1;

            return VisiteMedicale::create($donnees);
        });
    }
}