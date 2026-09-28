<?php

namespace Database\Factories;

use App\Domain\Personnel\Models\Salarie;
use App\Domain\Sst\Enums\StatutHabilitation;
use App\Domain\Sst\Models\Habilitation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class HabilitationFactory extends Factory
{
    protected $model = Habilitation::class;

    public function definition(): array
    {
        return [
            'entreprise_id' => 1,
            'salarie_id' => Salarie::factory(),
            'cle_soumission' => (string) Str::uuid(),
            'categorie' => 'Électrique',
            'intitule' => $this->faker->sentence(4),
            'portee' => $this->faker->paragraph(),
            'emetteur' => 'APAVE',
            'date_debut' => now()->subMonths(6),
            'date_fin' => now()->addMonths(18),
            'date_revue' => now()->addYear(),
            'statut' => StatutHabilitation::ACTIVE->value,
            'cree_par' => 1,
            'modifie_par' => 1,
            'revision' => 1,
            'etat' => 1,
        ];
    }

    public function expiree(): static
    {
        return $this->state([
            'statut' => StatutHabilitation::EXPIREE->value,
            'date_fin' => now()->subDays(5),
        ]);
    }

    public function expireBientot(): static
    {
        return $this->state([
            'date_fin' => now()->addDays(30),
        ]);
    }
}