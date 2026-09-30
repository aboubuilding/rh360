<?php

namespace Database\Factories;

use App\Domain\Organisation\Models\Poste;
use App\Domain\Organisation\Models\Structure;
use Illuminate\Database\Eloquent\Factories\Factory;

class PosteFactory extends Factory
{
    protected $model = Poste::class;

    public function definition(): array
    {
        return [
            'entreprise_id' => 1,
            // Les postes sont rattachés à une structure existante (créée par le seed d'organisation)
            'structure_id' => fn () => Structure::query()->withoutGlobalScopes()->value('id'),
            'code' => 'P-FACT-'.$this->faker->unique()->numerify('####'),
            'intitule' => $this->faker->jobTitle(),
            'categorie' => 'Employé',
            'effectif_cible' => 1,
            'actif' => true,
            'etat' => 1,
        ];
    }
}
