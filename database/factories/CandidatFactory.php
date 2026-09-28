<?php

namespace Database\Factories;

use App\Domain\Recrutement\Enums\DecisionCandidat;
use App\Domain\Recrutement\Enums\EtapeCandidat;
use App\Domain\Recrutement\Enums\SourceCandidat;
use App\Domain\Recrutement\Models\Candidat;
use Illuminate\Database\Eloquent\Factories\Factory;

class CandidatFactory extends Factory
{
    protected $model = Candidat::class;

    public function definition(): array
    {
        return [
            'entreprise_id' => 1,
            'besoin_id' => null,
            'nom' => strtoupper($this->faker->lastName()),
            'prenoms' => $this->faker->firstName(),
            'email' => $this->faker->unique()->safeEmail(),
            'telephone' => '+228 9' . $this->faker->numerify('#######'),
            'source' => SourceCandidat::ANNONCE->value,
            'etape' => EtapeCandidat::CANDIDATURE_RECUE->value,
            'decision' => DecisionCandidat::EN_ATTENTE->value,
            'etat' => 1,
        ];
    }

    public function retenu(): static
    {
        return $this->state([
            'etape' => EtapeCandidat::OFFRE->value,
            'decision' => DecisionCandidat::RETENU->value,
            'score' => 85,
        ]);
    }
}