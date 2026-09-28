<?php

namespace Database\Factories;

use App\Domain\Conges\Models\SoldeConge;
use App\Domain\Conges\Models\TypeConge;
use App\Domain\Personnel\Models\Salarie;
use Illuminate\Database\Eloquent\Factories\Factory;

class SoldeCongeFactory extends Factory
{
    protected $model = SoldeConge::class;

    public function definition(): array
    {
        return [
            'entreprise_id' => 1,
            'salarie_id' => Salarie::factory(),
            'type_conge_id' => TypeConge::factory(),
            'annee' => now()->year,
            'solde_ouverture' => 0,
            'acquis' => 30,
            'ajustement' => 0,
            'consomme' => 0,
            'reserve' => 0,
            'etat' => 1,
        ];
    }
}