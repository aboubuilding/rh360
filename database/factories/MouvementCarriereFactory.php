<?php

namespace Database\Factories;

use App\Domain\Carriere\Enums\StatutMouvement;
use App\Domain\Carriere\Enums\TypeMouvement;
use App\Domain\Carriere\Models\MouvementCarriere;
use App\Domain\Personnel\Models\Salarie;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class MouvementCarriereFactory extends Factory
{
    protected $model = MouvementCarriere::class;

    public function definition(): array
    {
        return [
            'entreprise_id' => 1,
            'numero_mouvement' => 'MOV-FACT-' . strtoupper(Str::random(6)),
            'salarie_id' => Salarie::factory(),
            'type_mouvement' => TypeMouvement::AFFECTATION->value,
            'statut' => StatutMouvement::BROUILLON->value,
            'date_proposition' => now(),
            'type_source' => 'manual',
            'cree_par' => 1,
            'etat' => 1,
        ];
    }

    public function propose(): static
    {
        return $this->state(['statut' => StatutMouvement::PROPOSE->value]);
    }

    public function valide(): static
    {
        return $this->state([
            'statut' => StatutMouvement::VALIDE->value,
            'date_decision' => now(),
        ]);
    }

    public function programme(): static
    {
        return $this->state([
            'statut' => StatutMouvement::PROGRAMME->value,
            'date_effet' => now()->addDays(15),
        ]);
    }

    public function effectif(): static
    {
        return $this->state([
            'statut' => StatutMouvement::EFFECTIF->value,
            'date_effet' => now()->subDays(5),
        ]);
    }

    public function interim(): static
    {
        return $this->state([
            'type_mouvement' => TypeMouvement::INTERIM->value,
            'date_fin_prevue' => now()->addMonths(3),
        ]);
    }
}