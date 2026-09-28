<?php

namespace App\Domain\Sst\Actions;

use App\Domain\Sst\Enums\StatutEvenementSecurite;
use App\Domain\Sst\Enums\StatutExterneEvenement;
use App\Domain\Sst\Models\EvenementSecurite;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DeclarerEvenementSecurite
{
    public function executer(array $donnees): EvenementSecurite
    {
        return DB::transaction(function () use ($donnees) {
            $donnees['entreprise_id'] = auth()->user()->entreprise_id;
            $donnees['cle_soumission'] = $donnees['cle_soumission'] ?? (string) Str::uuid();
            $donnees['date_declaration'] = $donnees['date_declaration'] ?? now();
            $donnees['statut'] = StatutEvenementSecurite::DECLARE->value;
            $donnees['statut_externe'] = StatutExterneEvenement::AUCUN->value;
            $donnees['priorite'] = $donnees['priorite'] ?? 'normal';
            $donnees['cree_par'] = auth()->id();
            $donnees['modifie_par'] = auth()->id();
            $donnees['revision'] = 1;
            $donnees['etat'] = 1;

            $evenement = EvenementSecurite::create($donnees);

            // Attacher les participants
            if (! empty($donnees['participants'] ?? [])) {
                $evenement->participants()->sync($donnees['participants']);
            }

            return $evenement->fresh(['participants']);
        });
    }
}