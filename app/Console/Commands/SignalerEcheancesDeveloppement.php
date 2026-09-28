<?php

namespace App\Console\Commands;

use App\Domain\Administration\Models\Entreprise;
use App\Domain\Formation\Models\PlanFormation;
use App\Domain\Formation\Models\SessionFormation;
use App\Domain\Performance\Models\CampagneEvaluation;
use App\Domain\Recrutement\Models\BesoinRecrutement;
use Illuminate\Console\Command;

class SignalerEcheancesDeveloppement extends Command
{
    protected $signature = 'rh:signaler-echeances-developpement
                            {--entreprise= : ID d\'une entreprise spécifique}';
    protected $description = 'Signale les échéances Formation / Performance / Recrutement';

    public function handle(): int
    {
        $entrepriseId = $this->option('entreprise');

        $entreprises = $entrepriseId
            ? Entreprise::where('id', $entrepriseId)->get()
            : Entreprise::where('actif', true)->get();

        foreach ($entreprises as $entreprise) {
            $this->info("=== Entreprise {$entreprise->id} : {$entreprise->nom} ===");

            // Sessions de formation à venir dans 30 jours
            $sessions = SessionFormation::where('entreprise_id', $entreprise->id)
                ->where('statut', 'programmee')
                ->whereBetween('date_debut', [now(), now()->addDays(30)])
                ->count();

            // Campagnes d'évaluation en cours
            $campagnesEnCours = CampagneEvaluation::where('entreprise_id', $entreprise->id)
                ->where('statut', 'en_cours')
                ->count();

            // Besoins de recrutement actifs
            $besoinsActifs = BesoinRecrutement::where('entreprise_id', $entreprise->id)
                ->whereIn('statut', ['valide', 'en_cours'])
                ->count();

            // Plans avec budget dépassé
            $plansDepasses = PlanFormation::where('entreprise_id', $entreprise->id)
                ->get()
                ->filter(function ($plan) {
                    $consomme = $plan->sessions()->sum('cout_reel');
                    return $consomme > (float) $plan->montant_budget;
                })
                ->count();

            $this->line("• Sessions de formation à venir (30 j) : {$sessions}");
            $this->line("• Campagnes d'évaluation en cours : {$campagnesEnCours}");
            $this->line("• Besoins de recrutement actifs : {$besoinsActifs}");
            $this->line("• Plans de formation en dépassement budgétaire : {$plansDepasses}");
        }

        return self::SUCCESS;
    }
}