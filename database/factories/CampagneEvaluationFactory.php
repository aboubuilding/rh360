<?php

namespace Database\Factories;

use App\Domain\Performance\Enums\StatutCampagne;
use App\Domain\Performance\Models\CampagneEvaluation;
use Illuminate\Database\Eloquent\Factories\Factory;

class CampagneEvaluationFactory extends Factory
{
    protected $model = CampagneEvaluation::class;

    public function definition(): array
    {
        return [
            'entreprise_id' => 1,
            'intitule' => 'Campagne ' . $this->faker->year(),
            'annee' => now()->year,
            'date_debut' => now()->startOfYear(),
            'date_fin' => now()->addMonths(3),
            'statut' => StatutCampagne::EN_COURS->value,
            'etat' => 1,
        ];
    }
}