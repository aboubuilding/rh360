<?php

namespace App\Domain\Recrutement\Listeners;

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Recrutement\Events\CandidatRetenu;

class NotifierCandidatRetenu
{
    public function handle(CandidatRetenu $event): void
    {
        $candidat = $event->candidat;

        // Notifier les RH et DRH
        $utilisateurs = Utilisateur::where('entreprise_id', $candidat->entreprise_id)
            ->whereIn('role', ['rh', 'drh'])
            ->where('actif', true)
            ->get();

        app(\App\Domain\Administration\Services\ServiceAudit::class)->tracer(
            'recrutement.candidat.retenu',
            $candidat,
            [
                'nom' => $candidat->nom_complet,
                'besoin' => $candidat->besoin?->reference,
            ]
        );
    }
}