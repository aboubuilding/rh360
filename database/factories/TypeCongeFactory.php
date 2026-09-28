<?php

namespace Database\Factories;

use App\Domain\Conges\Enums\CategorieTypeConge;
use App\Domain\Conges\Enums\ImpactAnciennete;
use App\Domain\Conges\Enums\ImpactCongeAnnuel;
use App\Domain\Conges\Enums\TraitementSalarial;
use App\Domain\Conges\Enums\UniteConge;
use App\Domain\Conges\Models\TypeConge;
use Illuminate\Database\Eloquent\Factories\Factory;

class TypeCongeFactory extends Factory
{
    protected $model = TypeConge::class;

    public function definition(): array
    {
        return [
            'entreprise_id' => 1,
            'code' => strtoupper($this->faker->unique()->lexify('???')),
            'nom' => $this->faker->words(3, true),
            'categorie' => CategorieTypeConge::CONGE->value,
            'unite' => UniteConge::JOUR_CALENDAIRE->value,
            'droit_annuel' => 30,
            'remunere' => true,
            'justificatif_requis' => false,
            'traitement_salarial' => TraitementSalarial::MAINTIEN->value,
            'impact_conge_annuel' => ImpactCongeAnnuel::AUCUNE->value,
            'impact_anciennete' => ImpactAnciennete::MAINTENUE->value,
            'autorisation_prealable_requise' => false,
            'actif' => true,
            'etat' => 1,
        ];
    }

    public function annuel(): static
    {
        return $this->state([
            'code' => 'CA-TEST-' . $this->faker->unique()->numerify('###'),
            'nom' => 'Congé annuel',
            'droit_annuel' => 30,
        ]);
    }

    public function sansDroit(): static
    {
        return $this->state(['droit_annuel' => 0]);
    }
}