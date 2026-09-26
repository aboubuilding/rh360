<?php

namespace App\Domain\Organisation\Actions;

use App\Domain\Organisation\Models\Structure;

class CreerStructure
{
    public function executer(array $donnees): Structure
    {
        return Structure::create($donnees);
    }
}