<?php

namespace App\Domain\Recrutement\Actions;

use App\Domain\Recrutement\Enums\StatutBesoinRecrutement;
use App\Domain\Recrutement\Models\BesoinRecrutement;
use App\Domain\Recrutement\Services\GenerateurReferenceBesoin;
use Illuminate\Support\Facades\DB;

class CreerBesoinRecrutement
{
    public function __construct(private GenerateurReferenceBesoin $generateur) {}

    public function executer(array $donnees): BesoinRecrutement
    {
        return DB::transaction(function () use ($donnees) {
            $entrepriseId = auth()->user()->entreprise_id;

            $donnees['entreprise_id'] = $entrepriseId;
            $donnees['reference'] = $donnees['reference'] ?? $this->generateur->generer($entrepriseId);
            $donnees['statut'] = StatutBesoinRecrutement::A_VALIDER->value;
            $donnees['etat'] = 1;

            return BesoinRecrutement::create($donnees);
        });
    }
}