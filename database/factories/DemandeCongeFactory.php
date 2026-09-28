<?php

namespace Database\Factories;

use App\Domain\Conges\Enums\StatutDemandeConge;
use App\Domain\Conges\Models\DemandeConge;
use App\Domain\Conges\Models\TypeConge;
use App\Domain\Personnel\Models\Salarie;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class DemandeCongeFactory extends Factory
{
    protected $model = DemandeConge::class;

    public function definition(): array
    {
        $debut = now()->addDays(15);
        $reprise = $debut->copy()->addDays(14);

        return [
            'entreprise_id' => 1,
            'numero_demande' => 'DEM-FACT-' . strtoupper(Str::random(6)),
            'salarie_id' => Salarie::factory(),
            'type_conge_id' => TypeConge::factory(),
            'date_demande' => now(),
            'date_debut' => $debut,
            'date_reprise' => $reprise,
            'duree_jours' => 14,
            'statut' => StatutDemandeConge::BROUILLON->value,
            'cree_par' => 1,
            'etat' => 1,
        ];
    }

    public function soumise(): static
    {
        return $this->state(['statut' => StatutDemandeConge::SOUMISE->value]);
    }

    public function autorisee(): static
    {
        return $this->state([
            'statut' => StatutDemandeConge::AUTORISEE->value,
            'date_decision' => now(),
        ]);
    }

    public function enCours(): static
    {
        return $this->state([
            'statut' => StatutDemandeConge::EN_COURS->value,
            'date_debut' => now()->subDays(5),
            'date_reprise' => now()->addDays(9),
        ]);
    }

    public function enRetard(): static
    {
        return $this->state([
            'statut' => StatutDemandeConge::EN_COURS->value,
            'date_debut' => now()->subDays(20),
            'date_reprise' => now()->subDays(2),
        ]);
    }

    public function terminee(): static
    {
        return $this->state([
            'statut' => StatutDemandeConge::REPRISE_CONFIRMEE->value,
            'date_debut' => now()->subDays(30),
            'date_reprise' => now()->subDays(15),
        ]);
    }
}