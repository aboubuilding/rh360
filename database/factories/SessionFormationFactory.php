<?php

namespace Database\Factories;

use App\Domain\Formation\Enums\StatutSessionFormation;
use App\Domain\Formation\Models\SessionFormation;
use Illuminate\Database\Eloquent\Factories\Factory;

class SessionFormationFactory extends Factory
{
    protected $model = SessionFormation::class;

    public function definition(): array
    {
        $debut = now()->addDays(15);

        return [
            'entreprise_id' => 1,
            'plan_formation_id' => null,
            'intitule' => $this->faker->sentence(3),
            'prestataire' => $this->faker->company(),
            'localisation' => $this->faker->city(),
            'date_debut' => $debut,
            'date_fin' => $debut->copy()->addDays(2),
            'duree_heures' => 14,
            'cout_reel' => $this->faker->randomFloat(2, 100000, 1000000),
            'statut' => StatutSessionFormation::PROGRAMMEE->value,
            'etat' => 1,
        ];
    }

    public function terminee(): static
    {
        return $this->state([
            'date_debut' => now()->subDays(30),
            'date_fin' => now()->subDays(28),
            'statut' => StatutSessionFormation::TERMINEE->value,
        ]);
    }
}