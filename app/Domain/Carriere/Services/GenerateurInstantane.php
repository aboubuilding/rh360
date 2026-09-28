<?php

namespace App\Domain\Carriere\Services;

use App\Domain\Carriere\Models\InstantaneCarriere;
use App\Domain\Carriere\Models\MouvementCarriere;
use App\Domain\Carriere\Models\SituationCarriere;

class GenerateurInstantane
{
    /**
     * Capture la situation AVANT l'application d'un mouvement.
     */
    public function capturerAvant(MouvementCarriere $mouvement): array
    {
        $situation = SituationCarriere::where('salarie_id', $mouvement->salarie_id)->first();

        return [
            'situation_carriere' => $situation ? $this->extraireSituation($situation) : null,
            'affectation' => $this->extraireAffectationCourante($mouvement->salarie_id),
        ];
    }

    /**
     * Enregistre l'instantané complet (avant + après).
     */
    public function enregistrer(MouvementCarriere $mouvement, array $avant, array $apres): InstantaneCarriere
    {
        return InstantaneCarriere::updateOrCreate(
            ['mouvement_id' => $mouvement->id],
            [
                'entreprise_id' => $mouvement->entreprise_id,
                'date_effet' => $mouvement->date_effet ?? now(),
                'details' => [
                    'nature' => $mouvement->type_mouvement->value,
                    'avant' => $avant,
                    'apres' => $apres,
                ],
            ]
        );
    }

    private function extraireSituation(SituationCarriere $s): array
    {
        return [
            'position_classification_id' => $s->position_classification_id,
            'position_ouverture_id' => $s->position_ouverture_id,
            'date_reference_ouverture' => $s->date_reference_ouverture?->format('Y-m-d'),
            'date_effet_categorie' => $s->date_effet_categorie?->format('Y-m-d'),
            'date_effet_classe' => $s->date_effet_classe?->format('Y-m-d'),
            'date_effet_echelon' => $s->date_effet_echelon?->format('Y-m-d'),
            'date_reference_avancement' => $s->date_reference_avancement?->format('Y-m-d'),
            'statut_fiabilite' => $s->statut_fiabilite?->value,
        ];
    }

    private function extraireAffectationCourante(int $salarieId): ?array
    {
        $aff = \App\Domain\Personnel\Models\Affectation::query()
            ->where('salarie_id', $salarieId)
            ->where('en_cours', true)
            ->first();

        if (! $aff) return null;

        return [
            'structure_id' => $aff->structure_id,
            'poste_id' => $aff->poste_id,
            'date_debut' => $aff->date_debut?->format('Y-m-d'),
        ];
    }
}