<?php

namespace App\Domain\Paie\Actions;

use App\Domain\Carriere\Models\SituationCarriere;
use App\Domain\Paie\Models\ElementPaieSalarie;
use App\Domain\Paie\Models\RubriquePaie;

class AffecterElementFixe
{
    public function executer(int $salarieId, int $rubriqueId, float $montant, ?string $observations = null): ElementPaieSalarie
    {
        // Le salaire de base n'est jamais inférieur au minimum de la position du salarié (barème).
        if (RubriquePaie::whereKey($rubriqueId)->value('code') === 'SAL-BASE') {
            $position = SituationCarriere::where('salarie_id', $salarieId)->first()?->positionClassification;
            if ($position?->salaire_minimum && $montant < $position->salaire_minimum) {
                throw new \DomainException('Le salaire de base ne peut pas être inférieur au minimum de la position '
                    .$position->code.' ('.number_format($position->salaire_minimum, 0, ',', ' ').' FCFA).');
            }
        }

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