<?php

namespace Database\Factories;

use App\Domain\Paie\Enums\ModeCalculRubrique;
use App\Domain\Paie\Enums\NatureRubrique;
use App\Domain\Paie\Enums\RecurrenceRubrique;
use App\Domain\Paie\Enums\TraitementFiscal;
use App\Domain\Paie\Models\RubriquePaie;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class RubriquePaieFactory extends Factory
{
    protected $model = RubriquePaie::class;

    public function definition(): array
    {
        return [
            'entreprise_id' => 1,
            'code' => strtoupper(Str::random(8)),
            'nom' => $this->faker->words(3, true),
            'nature' => NatureRubrique::GAIN->value,
            'recurrence' => RecurrenceRubrique::FIXE->value,
            'mode_calcul' => ModeCalculRubrique::MONTANT_FIXE->value,
            'taux' => 0,
            'montant_defaut' => 0,
            'imposable' => true,
            'traitement_fiscal' => TraitementFiscal::IMPOSABLE->value,
            'pourcentage_imposable' => 100,
            'soumis_cotisation' => true,
            'actif' => true,
            'etat' => 1,
        ];
    }

    public function gain(): static
    {
        return $this->state(['nature' => NatureRubrique::GAIN->value]);
    }

    public function retenue(): static
    {
        return $this->state([
            'nature' => NatureRubrique::RETENUE->value,
            'imposable' => false,
            'traitement_fiscal' => TraitementFiscal::EXONERE->value,
            'soumis_cotisation' => false,
        ]);
    }

    public function cotisation(): static
    {
        return $this->state([
            'nature' => NatureRubrique::COTISATION->value,
            'imposable' => false,
            'traitement_fiscal' => TraitementFiscal::EXONERE->value,
            'soumis_cotisation' => false,
        ]);
    }
}