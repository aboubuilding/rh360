<?php

namespace App\Domain\Contrats\Actions;

use App\Domain\Contrats\Enums\StatutContrat;
use App\Domain\Contrats\Models\Contrat;
use App\Domain\Contrats\Services\GenerateurAlertesContrats;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreerAvenant
{
    public function __construct(private GenerateurAlertesContrats $alertes) {}

    public function executer(Contrat $contratOrigine, array $donnees): Contrat
    {
        if (! $contratOrigine->estSigne()) {
            throw new \DomainException('Un avenant ne peut être créé qu\'à partir d\'un contrat signé.');
        }

        return DB::transaction(function () use ($contratOrigine, $donnees) {
            $donnees['entreprise_id'] = $contratOrigine->entreprise_id;
            $donnees['salarie_id'] = $contratOrigine->salarie_id;
            $donnees['parent_id'] = $contratOrigine->id;
            $donnees['statut'] = StatutContrat::BROUILLON->value;
            $donnees['cle_soumission'] = $donnees['cle_soumission'] ?? (string) Str::uuid();
            $donnees['conditions'] = $donnees['conditions'] ?? $contratOrigine->conditions ?? [];
            $donnees['cree_par'] = auth()->id() ?? 1;
            $donnees['revision'] = 1;
            $donnees['etat'] = 1;

            $avenant = Contrat::create($donnees);

            $this->alertes->genererPourContrat($avenant);

            return $avenant->fresh();
        });
    }
}