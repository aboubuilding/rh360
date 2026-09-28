
<?php

namespace App\Domain\Performance\Actions;

use App\Domain\Performance\Enums\StatutCampagne;
use App\Domain\Performance\Models\CampagneEvaluation;
use Illuminate\Support\Facades\DB;

class CreerCampagneEvaluation
{
    public function executer(array $donnees): CampagneEvaluation
    {
        return DB::transaction(function () use ($donnees) {
            $donnees['entreprise_id'] = auth()->user()->entreprise_id;
            $donnees['statut'] = $donnees['statut'] ?? StatutCampagne::PREPARATION->value;
            $donnees['etat'] = 1;

            return CampagneEvaluation::create($donnees);
        });
    }
}