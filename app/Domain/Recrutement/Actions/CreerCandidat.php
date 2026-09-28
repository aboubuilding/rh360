<?php

namespace App\Domain\Recrutement\Actions;

use App\Domain\Recrutement\Enums\DecisionCandidat;
use App\Domain\Recrutement\Enums\EtapeCandidat;
use App\Domain\Recrutement\Models\Candidat;

class CreerCandidat
{
    public function executer(array $donnees): Candidat
    {
        $donnees['entreprise_id'] = auth()->user()->entreprise_id;
        $donnees['etape'] = EtapeCandidat::CANDIDATURE_RECUE->value;
        $donnees['decision'] = DecisionCandidat::EN_ATTENTE->value;
        $donnees['etat'] = 1;

        return Candidat::create($donnees);
    }
}