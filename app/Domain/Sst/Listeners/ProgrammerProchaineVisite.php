<?php

namespace App\Domain\Sst\Listeners;

use App\Domain\Sst\Events\VisiteMedicaleRealisee;
use App\Domain\Sst\Enums\TypeVisiteMedicale;
use App\Domain\Sst\Actions\ProgrammerVisiteMedicale;

class ProgrammerProchaineVisite
{
    public function __construct(private ProgrammerVisiteMedicale $action) {}

    public function handle(VisiteMedicaleRealisee $event): void
    {
        $visite = $event->visite;

        // Si périodique et prochaine échéance calculée → programmer automatiquement
        if ($visite->type_visite === TypeVisiteMedicale::PERIODIQUE && $visite->date_prochaine_echeance) {
            $this->action->executer([
                'salarie_id' => $visite->salarie_id,
                'type_visite' => TypeVisiteMedicale::PERIODIQUE->value,
                'date_prevue' => $visite->date_prochaine_echeance,
                'visite_origine_id' => $visite->id,
            ]);
        }
    }
}