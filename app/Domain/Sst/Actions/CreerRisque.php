<?php

namespace App\Domain\Sst\Actions;

use App\Domain\Sst\Models\Risque;
use App\Domain\Sst\Services\GenerateurHistoriqueSst;
use App\Domain\Sst\Enums\NaturePieceSst;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreerRisque
{
    public function __construct(private GenerateurHistoriqueSst $historique) {}

    public function executer(array $donnees): Risque
    {
        return DB::transaction(function () use ($donnees) {
            $donnees['entreprise_id'] = auth()->user()->entreprise_id;
            $donnees['cle_soumission'] = (string) Str::uuid();
            $donnees['date_identification'] = $donnees['date_identification'] ?? now();
            // Revue annuelle par défaut ; la première évaluation l'ajuste selon le niveau de risque.
            $donnees['date_echeance_revue'] = $donnees['date_echeance_revue']
                ?? Carbon::parse($donnees['date_identification'])->addYear();
            $donnees['statut'] = 'active';
            $donnees['revision_perimetre'] = 1;
            $donnees['revision_mesures'] = 1;
            $donnees['cree_par'] = auth()->id();
            $donnees['modifie_par'] = auth()->id();
            $donnees['revision'] = 1;
            $donnees['etat'] = 1;

            $risque = Risque::create($donnees);

            // Historique initial
            $this->historique->enregistrer($risque, NaturePieceSst::RISQUE, 1, 'created');

            return $risque->fresh();
        });
    }
}