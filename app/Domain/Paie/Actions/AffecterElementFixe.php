<?php

namespace App\Domain\Paie\Actions;

use App\Domain\Paie\Models\ElementPaieSalarie;

class AffecterElementFixe
{
    public function executer(int $salarieId, int $rubriqueId, float $montant, ?string $observations = null): ElementPaieSalarie
    {
        return ElementPaieSalarie::updateOrCreate(
            ['salarie_id' => $salarieId, 'rubrique_id' => $rubriqueId],
            [
                'entreprise_id' => auth()->user()->entreprise_id,
                'montant' => $montant,
                'actif' => true,
                'observations' => $observations,
                'etat' => 1,
            ]
        );
    }

    public function supprimer(int $elementId): void
    {
        ElementPaieSalarie::where('id', $elementId)
            ->where('entreprise_id', auth()->user()->entreprise_id)
            ->delete();
    }
}