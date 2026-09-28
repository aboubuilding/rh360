<?php

namespace Database\Factories;

use App\Domain\Conges\Enums\QualificationAbsence;
use App\Domain\Conges\Enums\StatutTransmissionPaie;
use App\Domain\Conges\Models\Absence;
use App\Domain\Conges\Models\TypeConge;
use App\Domain\Personnel\Models\Salarie;
use Illuminate\Database\Eloquent\Factories\Factory;

class AbsenceFactory extends Factory
{
    protected $model = Absence::class;

    public function definition(): array
    {
        $debut = now()->subDays(2);

        return [
            'entreprise_id' => 1,
            'salarie_id' => Salarie::factory(),
            'type_conge_id' => TypeConge::factory(),
            'debut_le' => $debut,
            'fin_le' => $debut->copy()->addHours(8),
            'duree_heures' => 8,
            'motif' => 'Absence non justifiée',
            'statut' => 'constatée',
            'qualification' => QualificationAbsence::EN_ATTENTE->value,
            'statut_transmission_paie' => StatutTransmissionPaie::A_PREPARER->value,
            'cree_par' => 1,
            'etat' => 1,
        ];
    }

    public function ouverte(): static
    {
        return $this->state(['fin_le' => null]);
    }

    public function transmise(): static
    {
        return $this->state([
            'statut_transmission_paie' => StatutTransmissionPaie::TRANSMIS->value,
            'periode_paie' => now()->format('Y-m'),
            'transmis_paie_le' => now(),
        ]);
    }
}