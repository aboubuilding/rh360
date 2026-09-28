<?php

namespace App\Domain\Conges\Actions;

use App\Domain\Conges\Enums\StatutTransmissionPaie;
use App\Domain\Conges\Models\Absence;
use Illuminate\Support\Facades\DB;

class TransmettreAbsencesPaie
{
    /**
     * @param array<int> $absenceIds
     * @param string $periodePaie  Format : YYYY-MM
     */
    public function executer(array $absenceIds, string $periodePaie): int
    {
        return DB::transaction(function () use ($absenceIds, $periodePaie) {
            $absences = Absence::whereIn('id', $absenceIds)
                ->where('entreprise_id', auth()->user()->entreprise_id)
                ->get();

            $compteur = 0;
            foreach ($absences as $absence) {
                if ($absence->estTransmise()) continue;

                $absence->update([
                    'statut_transmission_paie' => StatutTransmissionPaie::TRANSMIS->value,
                    'periode_paie' => $periodePaie,
                    'transmis_paie_le' => now(),
                    'transmis_paie_par' => auth()->id(),
                ]);
                $compteur++;
            }

            return $compteur;
        });
    }
}