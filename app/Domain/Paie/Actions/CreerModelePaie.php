<?php

namespace App\Domain\Paie\Actions;

use App\Domain\Paie\Models\ModelePaie;
use App\Domain\Paie\Models\ModelePaieRubrique;
use Illuminate\Support\Facades\DB;

class CreerModelePaie
{
    public function executer(array $donnees, array $rubriques = []): ModelePaie
    {
        return DB::transaction(function () use ($donnees, $rubriques) {
            $modele = ModelePaie::create(array_merge($donnees, [
                'entreprise_id' => auth()->user()->entreprise_id,
                'etat' => 1,
            ]));

            foreach ($rubriques as $ordre => $rubrique) {
                ModelePaieRubrique::create([
                    'entreprise_id' => $modele->entreprise_id,
                    'modele_id' => $modele->id,
                    'rubrique_id' => $rubrique['rubrique_id'],
                    'montant_defaut' => $rubrique['montant_defaut'] ?? 0,
                    'obligatoire' => $rubrique['obligatoire'] ?? false,
                    'ordre' => $rubrique['ordre'] ?? ($ordre * 10),
                    'actif' => true,
                    'etat' => 1,
                ]);
            }

            return $modele->fresh(['rubriques']);
        });
    }
}