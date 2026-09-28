<?php

namespace Database\Factories;

use App\Domain\Paie\Enums\StatutPeriode;
use App\Domain\Paie\Models\PeriodePaie;
use Illuminate\Database\Eloquent\Factories\Factory;

class PeriodePaieFactory extends Factory
{
    protected $model = PeriodePaie::class;

    public function definition(): array
    {
        return [
            'entreprise_id' => 1,
            'annee' => now()->year,
            'mois' => $this->faker->numberBetween(1, 12),
            'statut' => StatutPeriode::OUVERTE->value,
            'etat' => 1,
        ];
    }

    public function calculee(): static
    {
        return $this->state(['statut' => StatutPeriode::CALCULEE->value]);
    }

    public function validee(): static
    {
        return $this->state([
            'statut' => StatutPeriode::VALIDEE->value,
            'valide_le' => now(),
            'valide_par' => 1,
        ]);
    }
}