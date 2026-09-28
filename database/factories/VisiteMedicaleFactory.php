<?php

namespace Database\Factories;

use App\Domain\Personnel\Models\Salarie;
use App\Domain\Sst\Enums\AptitudeMedicale;
use App\Domain\Sst\Enums\StatutVisiteMedicale;
use App\Domain\Sst\Enums\TypeVisiteMedicale;
use App\Domain\Sst\Models\VisiteMedicale;
use Illuminate\Database\Eloquent\Factories\Factory;

class VisiteMedicaleFactory extends Factory
{
    protected $model = VisiteMedicale::class;

    public function definition(): array
    {
        return [
            'entreprise_id' => 1,
            'salarie_id' => Salarie::factory(),
            'type_visite' => TypeVisiteMedicale::PERIODIQUE->value,
            'date_prevue' => now()->addDays(30),
            'statut' => StatutVisiteMedicale::PLANIFIEE->value,
            'aptitude' => AptitudeMedicale::EN_ATTENTE->value,
            'cree_par' => 1,
            'modifie_par' => 1,
            'revision' => 1,
            'etat' => 1,
        ];
    }

    public function realisee(): static
    {
        return $this->state([
            'statut' => StatutVisiteMedicale::REALISEE->value,
            'date_realisation' => now()->subMonth(),
            'aptitude' => AptitudeMedicale::APTE->value,
            'date_prochaine_echeance' => now()->addMonths(11),
        ]);
    }

    public function echue(): static
    {
        return $this->state([
            'date_prevue' => now()->subDays(5),
        ]);
    }

    public function avecRestrictions(): static
    {
        return $this->state([
            'aptitude' => AptitudeMedicale::APTE_AVEC_RESTRICTIONS->value,
            'restrictions' => 'Pas de port de charges lourdes',
        ]);
    }
}