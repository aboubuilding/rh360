<?php

namespace Database\Factories;

use App\Domain\Sst\Enums\StatutEvenementSecurite;
use App\Domain\Sst\Enums\StatutExterneEvenement;
use App\Domain\Sst\Enums\TypeEvenementSecurite;
use App\Domain\Sst\Models\EvenementSecurite;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class EvenementSecuriteFactory extends Factory
{
    protected $model = EvenementSecurite::class;

    public function definition(): array
    {
        return [
            'entreprise_id' => 1,
            'cle_soumission' => (string) Str::uuid(),
            'type_evenement' => TypeEvenementSecurite::INCIDENT->value,
            'date_survenance' => now()->subDays(5),
            'date_declaration' => now()->subDays(5)->addHours(3),
            'intitule' => $this->faker->sentence(4),
            'localisation' => $this->faker->randomElement(['Atelier', 'Entrepôt', 'Bureau', 'Zone de chargement']),
            'description' => $this->faker->paragraph(),
            'priorite' => 'normal',
            'statut' => StatutEvenementSecurite::DECLARE->value,
            'statut_externe' => StatutExterneEvenement::AUCUN->value,
            'cree_par' => 1,
            'modifie_par' => 1,
            'revision' => 1,
            'etat' => 1,
        ];
    }

    public function accident(): static
    {
        return $this->state([
            'type_evenement' => TypeEvenementSecurite::ACCIDENT_TRAVAIL->value,
            'priorite' => 'high',
        ]);
    }

    public function cloture(): static
    {
        return $this->state([
            'statut' => StatutEvenementSecurite::CLOTURE->value,
            'date_cloture' => now(),
            'synthese_cloture' => 'Analyse complète. Actions correctives mises en place.',
        ]);
    }
}