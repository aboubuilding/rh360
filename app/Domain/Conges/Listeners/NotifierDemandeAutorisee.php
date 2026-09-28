<?php

namespace App\Domain\Conges\Listeners;

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Conges\Events\DemandeAutorisee;

class NotifierDemandeAutorisee
{
    public function handle(DemandeAutorisee $event): void
    {
        $demande = $event->demande;

        // Notifier les RH de l'entreprise
        $utilisateurs = Utilisateur::query()
            ->where('entreprise_id', $demande->entreprise_id)
            ->whereIn('role', ['rh', 'manager'])
            ->where('actif', true)
            ->get();

        // Tracé
        app(\App\Domain\Administration\Services\ServiceAudit::class)->tracer(
            'conge.autorisee',
            $demande,
            [
                'numero' => $demande->numero_demande,
                'salarie' => $demande->salarie?->nom_complet,
                'date_debut' => $demande->date_debut?->format('Y-m-d'),
                'date_reprise' => $demande->date_reprise?->format('Y-m-d'),
                'duree' => $demande->duree_jours,
            ]
        );
    }
}