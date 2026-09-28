<?php

namespace Database\Factories;

use App\Domain\Paie\Models\HeureSupplementaire;
use App\Domain\Paie\Models\PeriodePaie;
use App\Domain\Personnel\Models\Salarie;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class HeureSupplementaireFactory extends Factory
{
    protected $model = HeureSupplementaire::class;

    public function definition(): array
    {
        return [
            'entreprise_id' => 1,
            'salarie_id' => Salarie::factory(),
            'reference' => 'HS-FACT-' . strtoupper(Str::random(6)),
            'debut_travail' => now()->startOfMonth(),
            'fin_travail' => now()->endOfMonth(),
            'periode_paiement_id' => PeriodePaie::factory(),
            'heures_hs20' => 5,
            'heures_hs40' => 0,
            'heures_hs65_jour' => 0,
            'heures_hs65_nuit' => 0,
            'heures_hs100' => 0,
            'salaire_base_fige' => 250000,
            'sursalaire_fige' => 0,
            'taux_horaire' => 1442.5,
            'est_rappel' => false,
            'cree_par' => 1,
            'etat' => 1,
        ];
    }
}