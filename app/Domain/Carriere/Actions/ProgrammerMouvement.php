<?php

namespace App\Domain\Carriere\Actions;

use App\Domain\Carriere\Enums\StatutMouvement;
use App\Domain\Carriere\Models\MouvementCarriere;
use App\Domain\Carriere\Services\AppliqueurMouvement;
use App\Domain\Carriere\Services\CircuitMouvement;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProgrammerMouvement
{
    public function __construct(
        private CircuitMouvement $circuit,
        private AppliqueurMouvement $appliqueur,
    ) {}

    /**
     * CDC §6 — Un acte validé à date d'effet future est programmé (appliqué par la tâche
     * quotidienne). Un acte à effet rétroactif ou du jour est appliqué immédiatement ;
     * les rappels de paie correspondants se génèrent ensuite (CDC §4 5.3).
     */
    public function executer(MouvementCarriere $mouvement, string $dateEffet): MouvementCarriere
    {
        $date = Carbon::parse($dateEffet)->startOfDay();

        return DB::transaction(function () use ($mouvement, $date) {
            $mouvement->update([
                'date_effet' => $date,
                'date_notification' => now(),
            ]);

            $mouvement = $this->circuit->transitionner(
                $mouvement,
                StatutMouvement::PROGRAMME,
                'programmation',
                "Programmé pour le {$date->format('d/m/Y')}",
            );

            if (! $date->isFuture()) {
                $mouvement = $this->appliqueur->appliquer($mouvement);
            }

            return $mouvement;
        });
    }
}
