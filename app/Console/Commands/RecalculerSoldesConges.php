<?php

namespace App\Console\Commands;

use App\Domain\Administration\Models\Entreprise;
use App\Domain\Conges\Models\TypeConge;
use App\Domain\Conges\Services\CalculateurSolde;
use App\Domain\Personnel\Models\Salarie;
use Illuminate\Console\Command;

class RecalculerSoldesConges extends Command
{
    protected $signature = 'rh:recalculer-soldes-conges
                            {--entreprise= : ID d\'une entreprise spécifique}
                            {--annee= : Année cible (par défaut : année courante)}';
    protected $description = 'Crée ou resynchronise les soldes de congés annuels';

    public function handle(CalculateurSolde $calculateur): int
    {
        $entrepriseId = $this->option('entreprise');
        $annee = (int) ($this->option('annee') ?? now()->year);

        $entreprises = $entrepriseId
            ? Entreprise::where('id', $entrepriseId)->get()
            : Entreprise::where('actif', true)->get();

        $total = 0;

        foreach ($entreprises as $entreprise) {
            $salaries = Salarie::where('entreprise_id', $entreprise->id)
                ->where('actif', true)
                ->get();

            $types = TypeConge::where('entreprise_id', $entreprise->id)
                ->where('actif', true)
                ->where('droit_annuel', '>', 0)
                ->get();

            if ($types->isEmpty()) continue;

            $compteur = 0;
            foreach ($salaries as $salarie) {
                foreach ($types as $type) {
                    try {
                        $solde = $calculateur->obtenir($salarie, $type, $annee);
                        $calculateur->resynchroniser($solde);
                        $compteur++;
                    } catch (\Throwable $e) {
                        $this->error("Erreur pour salarié {$salarie->id} / type {$type->id} : " . $e->getMessage());
                    }
                }
            }

            $this->info("Entreprise {$entreprise->id} : {$compteur} solde(s) traité(s).");
            $total += $compteur;
        }

        $this->info("Total : {$total} solde(s) traité(s).");
        return self::SUCCESS;
    }
}