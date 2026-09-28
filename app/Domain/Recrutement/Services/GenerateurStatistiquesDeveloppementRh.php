<?php

namespace App\Domain\DeveloppementRh\Services;

use App\Domain\Formation\Models\BesoinFormation;
use App\Domain\Formation\Models\PlanFormation;
use App\Domain\Formation\Models\SessionFormation;
use App\Domain\Performance\Models\CampagneEvaluation;
use App\Domain\Recrutement\Models\BesoinRecrutement;
use App\Domain\Recrutement\Models\Candidat;
use Carbon\Carbon;

class GenerateurStatistiquesDeveloppementRh
{
    public function globales(int $entrepriseId, Carbon $du, Carbon $au): array
    {
        return [
            'formation' => $this->formation($entrepriseId, $du, $au),
            'performance' => $this->performance($entrepriseId),
            'recrutement' => $this->recrutement($entrepriseId, $du, $au),
        ];
    }

    private function formation(int $entrepriseId, Carbon $du, Carbon $au): array
    {
        $sessions = SessionFormation::where('entreprise_id', $entrepriseId)
            ->whereBetween('date_debut', [$du, $au])
            ->get();

        return [
            'sessions_programmees' => $sessions->count(),
            'sessions_terminees' => $sessions->where('statut', 'terminee')->count(),
            'budget_engage' => round($sessions->sum('cout_reel'), 2),
            'heures_formation' => round($sessions->sum('duree_heures'), 2),
            'besoins_en_attente' => BesoinFormation::where('entreprise_id', $entrepriseId)
                ->where('statut', 'a_etudier')
                ->count(),
        ];
    }

    private function performance(int $entrepriseId): array
    {
        $campagnes = CampagneEvaluation::where('entreprise_id', $entrepriseId)
            ->where('annee', now()->year)
            ->get();

        return [
            'campagnes_actives' => $campagnes->where('statut', 'en_cours')->count(),
            'campagnes_cloturees' => $campagnes->where('statut', 'cloturee')->count(),
            'entretiens_a_realiser' => $campagnes->sum(fn ($c) => $c->entretiens()->whereNotIn('statut', ['valide', 'annule'])->count()),
        ];
    }

    private function recrutement(int $entrepriseId, Carbon $du, Carbon $au): array
    {
        $besoins = BesoinRecrutement::where('entreprise_id', $entrepriseId)->get();

        return [
            'besoins_actifs' => $besoins->whereIn('statut', ['valide', 'en_cours'])->count(),
            'besoins_pourvus' => $besoins->where('statut', 'pourvu')->count(),
            'candidats_en_cours' => Candidat::where('entreprise_id', $entrepriseId)
                ->enCours()
                ->count(),
            'recrutements_realises' => Candidat::where('entreprise_id', $entrepriseId)
                ->where('decision', 'retenu')
                ->whereBetween('date_integration', [$du, $au])
                ->count(),
        ];
    }
}