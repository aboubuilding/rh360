<?php

namespace Database\Factories;

use App\Domain\Personnel\Enums\StatutDossier;
use App\Domain\Personnel\Enums\StatutEmploi;
use App\Domain\Personnel\Models\Salarie;
use Illuminate\Database\Eloquent\Factories\Factory;

class SalarieFactory extends Factory
{
    protected $model = Salarie::class;

    public function definition(): array
    {
        return [
            'entreprise_id' => 1,
            'matricule' => 'MAT-FACT-'.$this->faker->unique()->numerify('#####'),
            'nom' => strtoupper($this->faker->lastName()),
            'prenoms' => $this->faker->firstName(),
            'sexe' => $this->faker->randomElement(['M', 'F']),
            'date_naissance' => $this->faker->dateTimeBetween('-55 years', '-22 years'),
            'nationalite' => 'Togolaise',
            'date_embauche' => now()->subYears(3)->startOfMonth(),
            'date_prise_service' => now()->subYears(3)->startOfMonth(),
            'type_contrat' => 'CDI',
            'statut_emploi' => StatutEmploi::ACTIF->value,
            'statut_dossier' => StatutDossier::COMPLET->value,
            'actif' => true,
            'etat' => 1,
        ];
    }

    public function incomplet(): static
    {
        return $this->state(['statut_dossier' => StatutDossier::INCOMPLET->value]);
    }

    public function sorti(): static
    {
        return $this->state([
            'statut_emploi' => StatutEmploi::SORTI->value,
            'actif' => false,
        ]);
    }
}
