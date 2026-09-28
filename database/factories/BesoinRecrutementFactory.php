<?php

namespace Database\Factories;

use App\Domain\Recrutement\Enums\StatutBesoinRecrutement;
use App\Domain\Recrutement\Models\BesoinRecrutement;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BesoinRecrutementFactory extends Factory
{
    protected $model = BesoinRecrutement::class;

    public function definition(): array
    {
        return [
            'entreprise_id' => 1,
            'reference' => 'REF-REC-' . now()->year . '-' . str_pad($this->faker->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'intitule_poste' => $this->faker->jobTitle(),
            'departement' => $this->faker->randomElement(['Informatique', 'RH', 'Commercial', 'Production']),
            'nombre_postes' => $this->faker->numberBetween(1, 3),
            'type_contrat' => 'CDI',
            'date_cible' => now()->addMonth(),
            'motif' => $this->faker->sentence(),
            'statut' => StatutBesoinRecrutement::VALIDE->value,
            'etat' => 1,
        ];
    }
}