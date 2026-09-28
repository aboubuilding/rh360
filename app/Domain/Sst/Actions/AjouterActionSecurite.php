<?php

namespace App\Domain\Sst\Actions;

use App\Domain\Sst\Enums\StatutActionSecurite;
use App\Domain\Sst\Models\ActionSecurite;
use App\Domain\Sst\Models\EvenementSecurite;
use Illuminate\Support\Str;

class AjouterActionSecurite
{
    public function executer(EvenementSecurite $evenement, array $donnees): ActionSecurite
    {
        return ActionSecurite::create([
            'entreprise_id' => $evenement->entreprise_id,
            'evenement_id' => $evenement->id,
            'cle_soumission' => (string) Str::uuid(),
            'intitule' => $donnees['intitule'],
            'responsable_salarie_id' => $donnees['responsable_salarie_id'],
            'date_echeance' => $donnees['date_echeance'],
            'statut' => StatutActionSecurite::A_FAIRE->value,
            'cree_par' => auth()->id(),
            'modifie_par' => auth()->id(),
            'revision' => 1,
            'etat' => 1,
        ]);
    }

    public function realiser(ActionSecurite $action, string $resultat): ActionSecurite
    {
        $action->update([
            'statut' => StatutActionSecurite::REALISEE->value,
            'date_realisation' => now(),
            'resultat' => $resultat,
            'revision' => $action->revision + 1,
            'modifie_par' => auth()->id(),
        ]);

        return $action->fresh();
    }
}