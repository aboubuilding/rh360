<?php

namespace App\Console\Commands;

use App\Domain\Administration\Models\Entreprise;
use App\Domain\Paie\Actions\CalculerPeriode;
use App\Domain\Paie\Actions\OuvrirPeriode;
use App\Domain\Paie\Enums\StatutPeriode;
use App\Domain\Paie\Models\PeriodePaie;
use Illuminate\Console\Command;

class CalculerPaieMensuelle extends Command
{
    protected $signature = 'rh:calculer-paie-mensuelle
                            {--entreprise= : ID d\'une entreprise spécifique}
                            {--periode= : ID d\'une période spécifique}
                            {--mois= : Mois cible (1-12)}
                            {--annee= : Année cible}';
    protected $description = 'Ouvre la période courante et calcule la paie mensuelle';

    public function handle(CalculerPeriode $calculer, OuvrirPeriode $ouvrir): int
    {
        $entrepriseId = $this->option('entreprise');
        $periodeId = $this->option('periode');
        $mois = (int) ($this->option('mois') ?? now()->month);
        $annee = (int) ($this->option('annee') ?? now()->year);

        // Cas 1 : calcul d'une période spécifique
        if ($periodeId) {
            $periode = PeriodePaie::find($periodeId);
            if (! $periode) {
                $this->error("Période {$periodeId} introuvable.");
                return self::FAILURE;
            }

            if ($periode->estFigee()) {
                $this->warn("Période {$periode->libelle} déjà validée : ignorée.");
                return self::SUCCESS;
            }

            $this->info("Calcul de la période {$periode->libelle}...");
            $resultats = $calculer->executer($periode);
            $this->info("→ {$resultats['bulletins_calcules']} bulletins, brut total : {$resultats['total_brut']} FCFA");

            return self::SUCCESS;
        }

        // Cas 2 : ouverture + calcul par entreprise
        $entreprises = $entrepriseId
            ? Entreprise::where('id', $entrepriseId)->get()
            : Entreprise::where('actif', true)->get();

        foreach ($entreprises as $entreprise) {
            // Ouvrir ou récupérer la période
            $periode = PeriodePaie::where('entreprise_id', $entreprise->id)
                ->where('annee', $annee)
                ->where('mois', $mois)
                ->first();

            if (! $periode) {
                try {
                    $periode = $ouvrir->executer($entreprise->id, $annee, $mois);
                    $this->info("Période {$periode->libelle} ouverte pour entreprise {$entreprise->id}.");
                } catch (\Throwable $e) {
                    $this->error("Erreur ouverture : " . $e->getMessage());
                    continue;
                }
            }

            if ($periode->estFigee()) {
                $this->warn("Période {$periode->libelle} déjà validée : ignorée.");
                continue;
            }

            try {
                $resultats = $calculer->executer($periode);
                $this->info("Entreprise {$entreprise->id} — {$resultats['bulletins_calcules']} bulletins calculés, brut total : {$resultats['total_brut']} FCFA");
            } catch (\Throwable $e) {
                $this->error("Erreur calcul : " . $e->getMessage());
            }
        }

        return self::SUCCESS;
    }
}