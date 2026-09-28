<?php

namespace App\Domain\Paie\Actions;

use App\Domain\Paie\Models\RubriquePaie;

class CreerRubriquePaie
{
    public function executer(array $donnees): RubriquePaie
    {
        return RubriquePaie::create(array_merge($donnees, [
            'entreprise_id' => auth()->user()->entreprise_id,
            'etat' => 1,
        ]));
    }
}