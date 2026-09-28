<?php

namespace App\Domain\Sst\Actions;

use App\Domain\Sst\Enums\StatutActionSecurite;
use App\Domain\Sst\Models\ActionRisque;
use App\Domain\Sst\Models\Risque;
use Illuminate\Support\Str;

class AjouterActionRisque
{
    public function executer(Risque $risque, array $donnees): ActionRisque
    {
        return ActionRisque::create([
            'entreprise_id' => $risque->entreprise_id,
            'risque_id' => $risque->id,
            'cle_soumission' => (string) Str::uuid(),
            'intitule' => $donnees['intitule'],
            'type_mesure' => $donnees['type_mesure'],
            'responsable_salarie_id' => $donnees['responsable_salarie_id'],
            'date_echeance' => $donnees['date_echeance'],
            'statut' => StatutActionSecurite::A_FAIRE->value,
            'cree_par' => auth()->id(),
            'modifie_par' => auth()->id(),
            'revision' => 1,
            'etat' => 1,
        ]);
    }
}