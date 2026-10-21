<?php

namespace App\Domain\Paie\Actions;

use App\Domain\Carriere\Models\SituationCarriere;
use App\Domain\Paie\Models\ElementPaieSalarie;
use App\Domain\Paie\Models\RubriquePaie;

class AffecterElementFixe
{
    public function executer(
        int $salarieId,
        int $rubriqueId,
        float $montant,
        ?string $observations = null,
        ?string $debut = null,
        ?string $fin = null,
        bool $exclu = false,
    ): ElementPaieSalarie {
        if ($debut && $fin && $fin < $debut) {
            throw new \DomainException('La date de fin de l\'élément doit suivre sa date de début.');
        }

        // Le salaire de base n'est jamais inférieur au minimum de la position du salarié (barème).
        if (RubriquePaie::whereKey($rubriqueId)->value('code') === 'SAL-BASE') {
            if ($exclu) {
                throw new \DomainException('Le salaire de base ne peut pas être exclu du bulletin.');
            }
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
                'montant' => $exclu ? 0 : $montant,
                'debut' => $debut,
                'fin' => $fin,
                'exclu' => $exclu,
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