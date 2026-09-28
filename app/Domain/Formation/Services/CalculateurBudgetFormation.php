<?php

namespace App\Domain\Formation\Services;

use App\Domain\Formation\Models\PlanFormation;

class CalculateurBudgetFormation
{
    /**
     * Retourne un état complet du budget d'un plan de formation.
     */
    public function etat(PlanFormation $plan): array
    {
        $sessions = $plan->sessions()->get();

        $budgetInitial = (float) $plan->montant_budget;
        $consomme = (float) $sessions->sum('cout_reel');
        $engage = (float) $sessions
            ->whereIn('statut', ['programmee', 'en_cours'])
            ->sum('cout_reel');
        $restant = $budgetInitial - $consomme - $engage;

        $tauxConsommation = $budgetInitial > 0
            ? round(($consomme / $budgetInitial) * 100, 2)
            : 0;

        return [
            'budget_initial' => $budgetInitial,
            'consomme' => $consomme,
            'engage' => $engage,
            'restant' => max(0, $restant),
            'taux_consommation' => $tauxConsommation,
            'nombre_sessions' => $sessions->count(),
            'sessions_terminees' => $sessions->where('statut', 'terminee')->count(),
        ];
    }

    /**
     * Vérifie qu'une nouvelle session peut être engagée dans le budget.
     */
    public function peutEngager(PlanFormation $plan, float $montant): bool
    {
        $etat = $this->etat($plan);
        return $etat['restant'] >= $montant;
    }
}