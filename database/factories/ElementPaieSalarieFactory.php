<?php

namespace Database\Factories;

use App\Domain\Paie\Models\ElementPaieSalarie;
use App\Domain\Paie\Models\RubriquePaie;
use App\Domain\Personnel\Models\Salarie;
use Illuminate\Database\Eloquent\Factories\Factory;

class ElementPaieSalarieFactory extends Factory
{
    protected $model = ElementPaieSalarie::class;

    public function definition(): array
    {
        return [
            'entreprise_id' => 1,
            'salarie_id' => Salarie::factory(),
            'rubrique_id' => RubriquePaie::factory(),
            'montant' => $this->faker->randomFloat(2, 10000, 500000),
            'actif' => true,
            'etat' => 1,
        ];
    }
}