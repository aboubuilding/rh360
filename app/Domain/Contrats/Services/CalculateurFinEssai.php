<?php

namespace App\Domain\Contrats\Services;

use App\Domain\Contrats\Models\Contrat;
use App\Domain\Contrats\Models\EvenementEssai;
use App\Domain\Contrats\Models\RegleContrat;
use Carbon\Carbon;

class CalculateurFinEssai
{
    /**
     * Calcule la fin théorique et la fin ajustée d'une période d'essai.
     */
    public function calculer(Contrat $contrat): array
    {
        $dateDebut = $contrat->date_debut;
        $regle = $this->chargerRegle($contrat);

        if (! $regle || ! $dateDebut) {
            return [
                'date_debut' => null,
                'duree_initiale_jours' => null,
                'date_fin_theorique' => null,
                'date_fin_ajustee' => null,
                'nombre_renouvellements_utilises' => 0,
                'nombre_renouvellements_autorises' => 0,
            ];
        }

        $dureeInitiale = $regle->duree_max_essai ?? null;
        $dateFinTheorique = $dureeInitiale
            ? $dateDebut->copy()->addDays($dureeInitiale)
            : null;

        // Durée réelle après suspensions
        $joursSuspendus = $this->calculerJoursSuspendus($contrat);
        $dateFinAjustee = $dateFinTheorique?->copy()->addDays($joursSuspendus);

        // Renouvellements
        $renouvellementsUtilises = $this->compterRenouvellements($contrat);
        $renouvellementsAutorises = $regle->nombre_renouvellements_max;

        return [
            'date_debut' => $dateDebut->format('Y-m-d'),
            'duree_initiale_jours' => $dureeInitiale,
            'date_fin_theorique' => $dateFinTheorique?->format('Y-m-d'),
            'date_fin_ajustee' => $dateFinAjustee?->format('Y-m-d'),
            'jours_suspendus' => $joursSuspendus,
            'nombre_renouvellements_utilises' => $renouvellementsUtilises,
            'nombre_renouvellements_autorises' => $renouvellementsAutorises,
            'peut_renouveler' => $renouvellementsUtilises < $renouvellementsAutorises,
        ];
    }

    public function chargerRegle(Contrat $contrat): ?RegleContrat
    {
        $position = $contrat->positionClassification;
        $categorieId = $position?->categorie_id;

        return RegleContrat::query()
            ->where('entreprise_id', $contrat->entreprise_id)
            ->where('type_contrat', $contrat->type_contrat)
            ->when($categorieId, fn ($q) => $q->where('categorie_id', $categorieId))
            ->where('date_effet', '<=', $contrat->date_debut ?? now())
            ->orderByDesc('date_effet')
            ->first();
    }

    private function calculerJoursSuspendus(Contrat $contrat): int
    {
        return $contrat->evenementsEssai()
            ->where('nature', 'suspension')
            ->where('statut', 'approved')
            ->get()
            ->sum(function (EvenementEssai $e) {
                $debut = $e->details['date_debut'] ?? null;
                $fin = $e->details['date_fin'] ?? null;
                if (! $debut || ! $fin) return 0;
                return Carbon::parse($debut)->diffInDays(Carbon::parse($fin)) + 1;
            });
    }

    private function compterRenouvellements(Contrat $contrat): int
    {
        return $contrat->evenementsEssai()
            ->where('nature', 'renouvellement')
            ->where('statut', 'approved')
            ->count();
    }
}