<?php

namespace App\Domain\Conges\Listeners;

use App\Domain\Conges\Events\RepriseConfirmee;
use App\Domain\Conges\Services\CalculateurSolde;

class ResynchroniserSoldeApresReprise
{
    public function __construct(private CalculateurSolde $calculateur) {}

    public function handle(RepriseConfirmee $event): void
    {
        $demande = $event->demande;
        $annee = $demande->date_debut?->year ?? now()->year;

        $solde = \App\Domain\Conges\Models\SoldeConge::where('salarie_id', $demande->salarie_id)
            ->where('type_conge_id', $demande->type_conge_id)
            ->where('annee', $annee)
            ->first();

        if ($solde) {
            $this->calculateur->resynchroniser($solde);
        }
    }
}