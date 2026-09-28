<?php

namespace App\Domain\Sst\Services;

use App\Domain\Personnel\Models\Salarie;
use App\Domain\Sst\Enums\TypeVisiteMedicale;
use Carbon\Carbon;

class CalculateurEcheanceVisite
{
    /**
     * Fréquence par défaut entre deux visites périodiques (en mois).
     */
    private const FREQUENCE_DEFAUT_MOIS = 12;

    /**
     * Fréquence rapprochée pour les salariés exposés à des risques.
     */
    private const FREQUENCE_EXPOSEE_MOIS = 6;

    /**
     * Calcule la prochaine échéance d'une visite périodique.
     */
    public function calculerProchaine(
        Salarie $salarie,
        TypeVisiteMedicale $type,
        ?Carbon $dateRealisation = null
    ): Carbon {
        if ($type !== TypeVisiteMedicale::PERIODIQUE) {
            // Les autres types ne programment pas automatiquement de suivi
            return ($dateRealisation ?? now())->copy()->addMonths(self::FREQUENCE_DEFAUT_MOIS);
        }

        $mois = $this->estExposeARisque($salarie)
            ? self::FREQUENCE_EXPOSEE_MOIS
            : self::FREQUENCE_DEFAUT_MOIS;

        return ($dateRealisation ?? now())->copy()->addMonths($mois);
    }

    /**
     * Détermine si un salarié est exposé à un risque SST actif (via affectations
     * et risques du poste).
     */
    private function estExposeARisque(Salarie $salarie): bool
    {
        $posteIds = $salarie->affectations()
            ->where('en_cours', true)
            ->pluck('poste_id')
            ->unique();

        if ($posteIds->isEmpty()) return false;

        return \App\Domain\Sst\Models\Risque::where('entreprise_id', $salarie->entreprise_id)
            ->whereIn('poste_id', $posteIds)
            ->where('statut', 'active')
            ->exists();
    }

    /**
     * Liste des visites échues ou à échéance proche (paramétrable).
     */
    public function aPlanifier(int $entrepriseId, int $jours = 30): \Illuminate\Support\Collection
    {
        return \App\Domain\Sst\Models\VisiteMedicale::where('entreprise_id', $entrepriseId)
            ->whereIn('statut', ['planned'])
            ->where(function ($q) use ($jours) {
                $q->where('date_prevue', '<', now())
                  ->orWhereBetween('date_prevue', [now(), now()->addDays($jours)]);
            })
            ->orderBy('date_prevue')
            ->get();
    }
}