<?php

namespace Database\Factories;

use App\Domain\Classification\Models\CategorieClassification;
use App\Domain\Interim\Enums\MotifInterim;
use App\Domain\Interim\Enums\StatutInterim;
use App\Domain\Interim\Models\InterimInterne;
use App\Domain\Organisation\Models\Poste;
use App\Domain\Personnel\Models\Salarie;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InterimInterne>
 */
class InterimInterneFactory extends Factory
{
    protected $model = InterimInterne::class;

    public function definition(): array
    {
        $debut = now()->startOfDay();

        return [
            'entreprise_id' => 1,
            'salarie_id' => Salarie::factory(),
            'poste_occupe_id' => fn () => Poste::query()->value('id'),
            'categorie_occupee_id' => fn () => CategorieClassification::query()->orderByDesc('ordre')->value('id'),
            'motif' => MotifInterim::POSTE_VACANT->value,
            'date_debut' => $debut,
            'date_fin_prevue' => $debut->copy()->addMonth(),
            'date_limite' => $debut->copy()->addMonths(2),
            'statut' => StatutInterim::EN_COURS->value,
            'cree_par' => 1,
            'etat' => 1,
        ];
    }

    public function motif(MotifInterim $motif): static
    {
        return $this->state(['motif' => $motif->value]);
    }

    public function aRegulariser(): static
    {
        return $this->state(['statut' => StatutInterim::A_REGULARISER->value]);
    }
}
