<?php

namespace Database\Factories;

use App\Domain\Carriere\Enums\StatutFiabilite;
use App\Domain\Carriere\Enums\StatutHistorique;
use App\Domain\Carriere\Enums\TypeSourceSituation;
use App\Domain\Carriere\Models\SituationCarriere;
use App\Domain\Classification\Models\PositionClassification;
use App\Domain\Personnel\Models\Salarie;
use Illuminate\Database\Eloquent\Factories\Factory;

class SituationCarriereFactory extends Factory
{
    protected $model = SituationCarriere::class;

    public function definition(): array
    {
        return [
            'entreprise_id' => 1,
            'salarie_id' => Salarie::factory(),
            'position_classification_id' => PositionClassification::factory(),
            'date_effet_categorie' => now()->subYears(3),
            'date_effet_classe' => now()->subYears(2),
            'date_effet_echelon' => now()->subYear(),
            'date_reference_avancement' => now()->subYear(),
            'type_source' => TypeSourceSituation::MANUEL->value,
            'statut_historique' => StatutHistorique::COMPLET->value,
            'statut_fiabilite' => StatutFiabilite::CONFIRME->value,
            'enregistre_le' => now(),
            'etat' => 1,
        ];
    }
}