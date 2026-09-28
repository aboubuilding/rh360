<?php

namespace Database\Factories;

use App\Domain\Contrats\Enums\StatutContrat;
use App\Domain\Contrats\Enums\TypeContrat;
use App\Domain\Contrats\Models\Contrat;
use App\Domain\Organisation\Models\Poste;
use App\Domain\Personnel\Models\Salarie;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ContratFactory extends Factory
{
    protected $model = Contrat::class;

    public function definition(): array
    {
        $debut = now()->subMonths(6);
        $type = $this->faker->randomElement([
            TypeContrat::CDI->value,
            TypeContrat::CDD->value,
        ]);

        return [
            'entreprise_id' => 1,
            'salarie_id' => Salarie::factory(),
            'reference' => 'CTR-FACT-' . strtoupper(Str::random(6)),
            'type_contrat' => $type,
            'date_debut' => $debut,
            'date_fin' => $type === TypeContrat::CDD->value ? $debut->copy()->addYear() : null,
            'poste_id' => Poste::factory(),
            'position_classification_id' => \App\Domain\Classification\Models\PositionClassification::factory(),
            'conditions' => ['salaire_base' => 250000],
            'statut' => StatutContrat::BROUILLON->value,
            'cle_soumission' => (string) Str::uuid(),
            'revision' => 1,
            'cree_par' => 1,
            'etat' => 1,
        ];
    }

    public function soumis(): static
    {
        return $this->state(['statut' => StatutContrat::SOUMIS->value]);
    }

    public function valide(): static
    {
        return $this->state(['statut' => StatutContrat::VALIDE->value]);
    }

    public function signe(): static
    {
        return $this->state([
            'statut' => StatutContrat::SIGNE->value,
            'date_signature' => now()->subDays(30),
            'reference_signee' => 'ACT-' . now()->format('Y') . '-0001',
        ]);
    }
}