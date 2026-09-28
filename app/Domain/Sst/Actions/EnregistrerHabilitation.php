<?php

namespace App\Domain\Sst\Actions;

use App\Domain\Sst\Enums\StatutHabilitation;
use App\Domain\Sst\Models\Habilitation;
use App\Domain\Sst\Services\GenerateurHistoriqueSst;
use App\Domain\Sst\Enums\NaturePieceSst;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EnregistrerHabilitation
{
    public function __construct(private GenerateurHistoriqueSst $historique) {}

    public function executer(array $donnees): Habilitation
    {
        return DB::transaction(function () use ($donnees) {
            $donnees['entreprise_id'] = auth()->user()->entreprise_id;
            $donnees['cle_soumission'] = (string) Str::uuid();
            $donnees['statut'] = StatutHabilitation::ACTIVE->value;
            $donnees['cree_par'] = auth()->id();
            $donnees['modifie_par'] = auth()->id();
            $donnees['revision'] = 1;
            $donnees['etat'] = 1;

            $habilitation = Habilitation::create($donnees);

            $this->historique->enregistrer($habilitation, NaturePieceSst::HABILITATION, 1, 'created');

            return $habilitation;
        });
    }
}