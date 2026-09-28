<?php

namespace App\Domain\Formation\Actions;

use App\Domain\Formation\Enums\StatutBesoinFormation;
use App\Domain\Formation\Models\BesoinFormation;

class ValiderBesoinFormation
{
    public function executer(BesoinFormation $besoin): BesoinFormation
    {
        if ($besoin->statut !== StatutBesoinFormation::A_ETUDIER) {
            throw new \DomainException('Seul un besoin à étudier peut être validé.');
        }

        $besoin->update(['statut' => StatutBesoinFormation::VALIDE->value]);

        return $besoin->fresh();
    }
}