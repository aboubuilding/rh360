<?php

namespace Database\Factories;

use App\Domain\Performance\Enums\StatutEntretien;
use App\Domain\Performance\Models\CampagneEvaluation;
use App\Domain\Performance\Models\EntretienEvaluation;
use App\Domain\Personnel\Models\Salarie;
use Illuminate\Database\Eloquent\Factories\Factory;

class EntretienEvaluationFactory extends Factory
{
    protected $model = EntretienEvaluation::class;

    public function definition(): array
    {
        return [
            'entreprise_id' => 1,
            'campagne_id' => CampagneEvaluation::factory(),
            'salarie_id' => Salarie::factory(),
            'statut' => StatutEntretien::A_PREPARER->value,
            'etat' => 1,
        ];
    }

    public function valide(): static
    {
        return $this->state([
            'statut' => StatutEntretien::VALIDE->value,
            'note_auto_evaluation' => 15,
            'note_manager' => 16,
            'note_finale' => 15.8,
            'date_entretien' => now()->subDays(5),
        ]);
    }
}