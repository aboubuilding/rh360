<?php

namespace App\Domain\Formation\Actions;

use App\Domain\Formation\Enums\StatutSessionFormation;
use App\Domain\Formation\Models\PlanFormation;
use App\Domain\Formation\Models\SessionFormation;
use App\Domain\Formation\Services\CalculateurBudgetFormation;
use Illuminate\Support\Facades\DB;

class CreerSessionFormation
{
    public function __construct(private CalculateurBudgetFormation $budget) {}

    public function executer(array $donnees): SessionFormation
    {
        return DB::transaction(function () use ($donnees) {
            // Vérifier le budget du plan si applicable
            if (! empty($donnees['plan_formation_id']) && ! empty($donnees['cout_reel'])) {
                $plan = PlanFormation::findOrFail($donnees['plan_formation_id']);

                if (! $this->budget->peutEngager($plan, (float) $donnees['cout_reel'])) {
                    throw new \DomainException(
                        'Budget insuffisant sur le plan de formation. Restant : '
                        . number_format($this->budget->etat($plan)['restant'], 0, ',', ' ') . ' FCFA'
                    );
                }
            }

            $donnees['entreprise_id'] = auth()->user()->entreprise_id;
            $donnees['statut'] = $donnees['statut'] ?? StatutSessionFormation::PROGRAMMEE->value;
            $donnees['etat'] = 1;

            return SessionFormation::create($donnees);
        });
    }
}