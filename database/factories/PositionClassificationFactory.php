<?php

namespace Database\Factories;

use App\Domain\Classification\Models\CategorieClassification;
use App\Domain\Classification\Models\PositionClassification;
use Illuminate\Database\Eloquent\Factories\Factory;

class PositionClassificationFactory extends Factory
{
    protected $model = PositionClassification::class;

    public function definition(): array
    {
        // Les positions sont rattachées à une catégorie existante (créée par le seed de classification)
        $categorie = CategorieClassification::query()->withoutGlobalScopes()->first();

        return [
            'referentiel_id' => $categorie?->referentiel_id,
            'categorie_id' => $categorie?->id,
            'code' => 'POS-FACT-'.$this->faker->unique()->numerify('####'),
            'montant_salaire' => 150000,
            'salaire_minimum' => 150000,
            'ordre' => 999,
            'actif' => true,
            'statut' => 'active',
            'etat' => 1,
        ];
    }
}
