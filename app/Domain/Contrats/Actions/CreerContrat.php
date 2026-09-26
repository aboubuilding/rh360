<?php

namespace App\Domain\Contrats\Actions;

use App\Domain\Contrats\Enums\StatutContrat;
use App\Domain\Contrats\Models\Contrat;
use App\Domain\Contrats\Services\GenerateurAlertesContrats;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreerContrat
{
    public function __construct(private GenerateurAlertesContrats $alertes) {}

    public function executer(array $donnees): Contrat
    {
        return DB::transaction(function () use ($donnees) {
            $donnees['entreprise_id'] = auth()->user()->entreprise_id;
            $donnees['statut'] = StatutContrat::BROUILLON->value;
            $donnees['cle_soumission'] = $donnees['cle_soumission'] ?? (string) Str::uuid();
            $donnees['cree_par'] = auth()->id() ?? 1;
            $donnees['revision'] = 1;
            $donnees['etat'] = 1;

            $contrat = Contrat::create($donnees);

            $this->alertes->genererPourContrat($contrat);

            return $contrat->fresh();
        });
    }
}