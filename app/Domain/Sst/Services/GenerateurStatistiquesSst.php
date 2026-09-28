<?php

namespace App\Domain\Sst\Services;

use App\Domain\Sst\Models\DotationEpi;
use App\Domain\Sst\Models\EvenementSecurite;
use App\Domain\Sst\Models\Habilitation;
use App\Domain\Sst\Models\Risque;
use App\Domain\Sst\Models\VisiteMedicale;
use Carbon\Carbon;

class GenerateurStatistiquesSst
{
    /**
     * Statistiques agrégées SST — JAMAIS nominatives.
     * Destiné aux profils Direction / Auditeur sans accès santé nominatif.
     */
    public function globales(int $entrepriseId, Carbon $du, Carbon $au): array
    {
        return [
            'visites_medicales' => $this->statsVisites($entrepriseId, $du, $au),
            'evenements_securite' => $this->statsEvenements($entrepriseId, $du, $au),
            'risques' => $this->statsRisques($entrepriseId),
            'epi' => $this->statsEpi($entrepriseId),
            'habilitations' => $this->statsHabilitations($entrepriseId),
        ];
    }

    private function statsVisites(int $entrepriseId, Carbon $du, Carbon $au): array
    {
        $query = VisiteMedicale::where('entreprise_id', $entrepriseId)
            ->whereBetween('date_prevue', [$du, $au]);

        $total = (clone $query)->count();
        $realisees = (clone $query)->where('statut', 'completed')->count();

        $aptitudes = (clone $query)
            ->where('statut', 'completed')
            ->selectRaw('aptitude, COUNT(*) as total')
            ->groupBy('aptitude')
            ->pluck('total', 'aptitude')
            ->all();

        return [
            'total_planifiees' => $total,
            'realisees' => $realisees,
            'taux_realisation' => $total > 0 ? round(($realisees / $total) * 100, 2) : 0,
            'repartition_aptitudes' => $aptitudes,
        ];
    }

    private function statsEvenements(int $entrepriseId, Carbon $du, Carbon $au): array
    {
        $query = EvenementSecurite::where('entreprise_id', $entrepriseId)
            ->whereBetween('date_survenance', [$du, $au]);

        $total = (clone $query)->count();

        $parType = (clone $query)
            ->selectRaw('type_evenement, COUNT(*) as total')
            ->groupBy('type_evenement')
            ->pluck('total', 'type_evenement')
            ->all();

        $parStatut = (clone $query)
            ->selectRaw('statut, COUNT(*) as total')
            ->groupBy('statut')
            ->pluck('total', 'statut')
            ->all();

        return [
            'total' => $total,
            'par_type' => $parType,
            'par_statut' => $parStatut,
        ];
    }

    private function statsRisques(int $entrepriseId): array
    {
        $query = Risque::where('entreprise_id', $entrepriseId)->where('statut', 'active');

        return [
            'total_actifs' => (clone $query)->count(),
            'par_famille' => (clone $query)
                ->selectRaw('famille, COUNT(*) as total')
                ->groupBy('famille')
                ->pluck('total', 'famille')
                ->all(),
        ];
    }

    private function statsEpi(int $entrepriseId): array
    {
        return [
            'total_actives' => DotationEpi::where('entreprise_id', $entrepriseId)
                ->whereIn('statut', ['issued', 'in_use'])
                ->count(),
            'a_remplacer' => DotationEpi::where('entreprise_id', $entrepriseId)
                ->where('statut', 'to_replace')
                ->count(),
            'expirees' => DotationEpi::where('entreprise_id', $entrepriseId)
                ->whereNotNull('date_expiration')
                ->where('date_expiration', '<', now())
                ->count(),
        ];
    }

    private function statsHabilitations(int $entrepriseId): array
    {
        return [
            'total_actives' => Habilitation::where('entreprise_id', $entrepriseId)
                ->where('statut', 'active')
                ->count(),
            'expirent_60j' => Habilitation::where('entreprise_id', $entrepriseId)
                ->where('statut', 'active')
                ->whereBetween('date_fin', [now(), now()->addDays(60)])
                ->count(),
            'expirees' => Habilitation::where('entreprise_id', $entrepriseId)
                ->where('statut', 'active')
                ->where('date_fin', '<', now())
                ->count(),
        ];
    }
}