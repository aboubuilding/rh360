<?php

namespace App\Console\Commands;

use App\Domain\Administration\Models\Entreprise;
use App\Domain\Sst\Models\DotationEpi;
use App\Domain\Sst\Models\Habilitation;
use App\Domain\Sst\Models\Risque;
use App\Domain\Sst\Models\VisiteMedicale;
use Illuminate\Console\Command;

class VerifierEcheancesSst extends Command
{
    protected $signature = 'rh:verifier-echeances-sst
                            {--entreprise= : ID d\'une entreprise spécifique}';
    protected $description = 'Vérifie les échéances SST (visites, EPI, habilitations, revues de risques)';

    public function handle(): int
    {
        $entrepriseId = $this->option('entreprise');

        $entreprises = $entrepriseId
            ? Entreprise::where('id', $entrepriseId)->get()
            : Entreprise::where('actif', true)->get();

        foreach ($entreprises as $entreprise) {
            $this->info("=== Entreprise {$entreprise->id} : {$entreprise->nom} ===");

            $visites = VisiteMedicale::where('entreprise_id', $entreprise->id)
                ->whereIn('statut', ['planned'])
                ->where('date_prevue', '<', now()->addDays(30))
                ->count();

            $epiExpirant = DotationEpi::where('entreprise_id', $entreprise->id)
                ->whereIn('statut', ['issued', 'in_use'])
                ->whereNotNull('date_expiration')
                ->where('date_expiration', '<', now()->addDays(30))
                ->count();

            $habilitationsExpirant = Habilitation::where('entreprise_id', $entreprise->id)
                ->where('statut', 'active')
                ->whereNotNull('date_fin')
                ->where('date_fin', '<', now()->addDays(60))
                ->count();

            $risquesARevoir = Risque::where('entreprise_id', $entreprise->id)
                ->where('statut', 'active')
                ->whereNotNull('date_echeance_revue')
                ->where('date_echeance_revue', '<', now()->addDays(30))
                ->count();

            $this->line("• Visites à programmer (30 j) : {$visites}");
            $this->line("• EPI à remplacer (30 j) : {$epiExpirant}");
            $this->line("• Habilitations à renouveler (60 j) : {$habilitationsExpirant}");
            $this->line("• Risques à revoir (30 j) : {$risquesARevoir}");
        }

        return self::SUCCESS;
    }
}