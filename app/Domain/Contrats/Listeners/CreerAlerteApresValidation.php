<?php

namespace App\Domain\Contrats\Listeners;

use App\Domain\Contrats\Events\ContratValide;
use App\Domain\Contrats\Services\GenerateurAlertesContrats;

class CreerAlerteApresValidation
{
    public function __construct(private GenerateurAlertesContrats $alertes) {}

    public function handle(ContratValide $event): void
    {
        $this->alertes->resynchroniser($event->contrat);
    }
}