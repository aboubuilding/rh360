<?php

namespace Database\Factories;

use App\Domain\Personnel\Models\Salarie;
use App\Domain\Sst\Enums\FamilleRisque;
use App\Domain\Sst\Models\Risque;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class RisqueFactory extends Factory
{
    protected $model = Risque::class;

    public function definition(): array
    {
        return [
            'entreprise_id' => 1,
            'cle_soumission' => (string) Str::uuid(),
            'intitule' => $this->faker->sentence(4),
            'famille' => FamilleRisque::PHYSIQUE->value,
            'site' => $this->faker->randomElement(['Site principal', 'Annexe', 'Entrepôt']),
            'poste_id' => null,
            'activite' => $this->faker->sentence(3),
            'danger' => $this->faker->paragraph(),
            'consequences' => $this->faker->sentence(6),
            'date_identification' => now()->subMonths(2),
            'responsable_salarie_id' => Salarie::factory(),
            'date_echeance_revue' => now()->addMonths(4),
            'statut' => 'active',
            'revision_perimetre' => 1,
            'revision_mesures' => 1,
            'cree_par' => 1,
            'modifie_par' => 1,
            'revision' => 1,
            'etat' => 1,
        ];
    }

    public function archive(): static
    {
        return $this->state([
            'statut' => 'archived',
            'motif_archivage' => 'Risque supprimé après mise en conformité',
        ]);
    }
}