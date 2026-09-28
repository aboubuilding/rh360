<?php

namespace App\Domain\Carriere\Actions;

use App\Domain\Carriere\Models\MouvementCarriere;
use Illuminate\Support\Facades\DB;

class ModifierMouvement
{
    public function executer(MouvementCarriere $mouvement, array $donnees): MouvementCarriere
    {
        if (! $mouvement->statut->estModifiable()) {
            throw new \DomainException('Ce mouvement ne peut plus être modifié.');
        }

        return DB::transaction(function () use ($mouvement, $donnees) {
            $mouvement->update($donnees);
            return $mouvement->fresh();
        });
    }
}