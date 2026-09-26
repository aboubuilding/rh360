<?php

namespace App\Domain\Classification\Actions;

use App\Domain\Classification\Models\ReferentielClassification;

class CreerReferentiel
{
    public function executer(array $donnees): ReferentielClassification
    {
        return ReferentielClassification::create($donnees);
    }
}