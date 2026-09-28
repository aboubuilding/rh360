<?php

namespace Database\Factories;

use App\Domain\Paie\Models\BulletinPaie;
use App\Domain\Paie\Models\PeriodePaie;
use App\Domain\Personnel\Models\Salarie;
use Illuminate\Database\Eloquent\Factories\Factory;

class BulletinPaieFactory extends Factory
{
    protected $model = BulletinPaie::class;

    public function definition(): array
    {
        $brut = 250000;

        return [
            'entreprise_id' => 1,
            'periode_id' => PeriodePaie::factory(),
            'salarie_id' => Salarie::factory(),
            'montant_brut' => $brut,
            'montant_retenues' => 45000,
            'montant_net' => $brut - 45000,
            'brut_imposable' => $brut,
            'retenues_sociales_deductibles' => 22500,
            'abattement_professionnel' => 63000,
            'deduction_charges_famille' => 0,
            'base_imposable' => 164500,
            'montant_irpp' => 8225,
            'calcule_le' => now(),
            'etat' => 1,
        ];
    }
}