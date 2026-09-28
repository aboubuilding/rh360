<?php

namespace App\Domain\Conges\Actions;

use App\Domain\Conges\Enums\QualificationAbsence;
use App\Domain\Conges\Enums\StatutTransmissionPaie;
use App\Domain\Conges\Models\Absence;
use Illuminate\Support\Facades\DB;

class EnregistrerAbsence
{
    public function executer(array $donnees): Absence
    {
        return DB::transaction(function () use ($donnees) {
            $donnees['entreprise_id'] = auth()->user()->entreprise_id;
            $donnees['statut'] = $donnees['statut'] ?? 'constatée';
            $donnees['qualification'] = QualificationAbsence::EN_ATTENTE->value;
            $donnees['statut_transmission_paie'] = StatutTransmissionPaie::A_PREPARER->value;
            $donnees['cree_par'] = auth()->id();
            $donnees['etat'] = 1;

            return Absence::create($donnees);
        });
    }
}