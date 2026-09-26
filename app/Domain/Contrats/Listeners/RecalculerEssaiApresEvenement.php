<?php

namespace App\Domain\Contrats\Listeners;

use App\Domain\Contrats\Events\EvenementEssaiValide;
use App\Domain\Contrats\Services\CalculateurFinEssai;
use App\Domain\Contrats\Services\GenerateurAlertesContrats;

class RecalculerEssaiApresEvenement
{
    public function __construct(
        private CalculateurFinEssai $calculateur,
        private GenerateurAlertesContrats $alertes,
    ) {}

    public function handle(EvenementEssaiValide $event): void
    {
        $contrat = $event->evenement->contrat;
        $this->calculateur->calculer($contrat);
        $this->alertes->resynchroniser($contrat);
    }
}