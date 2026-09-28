<?php

namespace App\Domain\Sst\Actions;

use App\Domain\Sst\Enums\StatutEvenementSecurite;
use App\Domain\Sst\Models\EvenementSecurite;

class CloturerEvenementSecurite
{
    public function executer(EvenementSecurite $evenement, string $synthese): EvenementSecurite
    {
        if (! $evenement->estCloturable()) {
            throw new \DomainException('Cet événement ne peut pas être clôturé.');
        }

        if (empty(trim($synthese))) {
            throw new \DomainException('La synthèse de clôture est obligatoire.');
        }

        $evenement->update([
            'statut' => StatutEvenementSecurite::CLOTURE->value,
            'date_cloture' => now(),
            'synthese_cloture' => $synthese,
            'revision' => $evenement->revision + 1,
            'modifie_par' => auth()->id(),
        ]);

        return $evenement->fresh();
    }
}