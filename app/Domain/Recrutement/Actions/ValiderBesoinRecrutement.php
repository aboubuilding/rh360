<?php

namespace App\Domain\Recrutement\Actions;

use App\Domain\Recrutement\Enums\StatutBesoinRecrutement;
use App\Domain\Recrutement\Models\BesoinRecrutement;

class ValiderBesoinRecrutement
{
    public function executer(BesoinRecrutement $besoin): BesoinRecrutement
    {
        if ($besoin->statut !== StatutBesoinRecrutement::A_VALIDER) {
            throw new \DomainException('Seul un besoin à valider peut être validé.');
        }

        $besoin->update(['statut' => StatutBesoinRecrutement::VALIDE->value]);

        return $besoin->fresh();
    }

    public function ouvrirRecrutement(BesoinRecrutement $besoin): BesoinRecrutement
    {
        if ($besoin->statut !== StatutBesoinRecrutement::VALIDE) {
            throw new \DomainException('Le besoin doit être validé avant d\'ouvrir le recrutement.');
        }

        $besoin->update(['statut' => StatutBesoinRecrutement::EN_COURS->value]);

        return $besoin->fresh();
    }
}