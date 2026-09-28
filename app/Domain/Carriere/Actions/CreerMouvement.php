<?php

namespace App\Domain\Carriere\Actions;

use App\Domain\Carriere\Enums\StatutMouvement;
use App\Domain\Carriere\Models\MouvementCarriere;
use App\Domain\Carriere\Services\GenerateurNumeroMouvement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreerMouvement
{
    public function __construct(private GenerateurNumeroMouvement $numeroteur) {}

    public function executer(array $donnees): MouvementCarriere
    {
        return DB::transaction(function () use ($donnees) {
            $entrepriseId = auth()->user()->entreprise_id;

            $donnees['entreprise_id'] = $entrepriseId;
            $donnees['numero_mouvement'] = $donnees['numero_mouvement']
                ?? $this->numeroteur->generer($entrepriseId);
            $donnees['statut'] = $donnees['statut'] ?? StatutMouvement::BROUILLON->value;
            $donnees['date_proposition'] = $donnees['date_proposition'] ?? now();
            $donnees['cree_par'] = auth()->id();
            $donnees['type_saisie_source'] = $donnees['type_saisie_source'] ?? 'normal';
            $donnees['etat'] = 1;

            return MouvementCarriere::create($donnees);
        });
    }
}