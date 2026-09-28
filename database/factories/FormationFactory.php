<?php

namespace Database\Factories;

use App\Domain\Formation\Enums\ModaliteFormation;
use App\Domain\Formation\Models\Formation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class FormationFactory extends Factory
{
    protected $model = Formation::class;

    public function definition(): array
    {
        return [
            'entreprise_id' => 1,
            'code' => 'FOR-' . strtoupper(Str::random(6)),
            'intitule' => $this->faker->sentence(3),
            'domaine' => $this->faker->randomElement(['Sécurité', 'Management', 'Informatique', 'Communication']),
            'objectif' => $this->faker->paragraph(),
            'duree_heures' => $this->faker->randomFloat(1, 4, 40),
            'modalite' => ModaliteFormation::PRESENTIEL->value,
            'actif' => true,
            'etat' => 1,
        ];
    }
}