<?php

namespace Database\Seeders;

use App\Domain\Conges\Actions\CreerDemandeConge;
use App\Domain\Conges\Enums\StatutDemandeConge;
use App\Domain\Conges\Models\DemandeConge;
use App\Domain\Conges\Models\TypeConge;
use App\Domain\Personnel\Models\Salarie;
use Illuminate\Database\Seeder;

class CongesDemoSeeder extends Seeder
{
    public function run(): void
    {
        $entrepriseId = 1;
        $superAdmin = \App\Domain\Administration\Models\Utilisateur::first();

        $salaries = Salarie::where('entreprise_id', $entrepriseId)
            ->where('actif', true)
            ->limit(5)
            ->get();

        if ($salaries->isEmpty()) {
            $this->command->warn('Aucun salarié. Exécutez SalariesDemoSeeder d\'abord.');
            return;
        }

        $typeCongeAnnuel = TypeConge::where('entreprise_id', $entrepriseId)
            ->where('code', 'CA')
            ->first();

        if (! $typeCongeAnnuel) {
            $this->command->warn('Type congé annuel absent. Exécutez TypesCongesSeeder d\'abord.');
            return;
        }

        $action = app(CreerDemandeConge::class);
        $compteur = 0;

        // Créer quelques demandes de congés à des états variés
        foreach ($salaries as $i => $salarie) {
            // Éviter les doublons
            if (DemandeConge::where('salarie_id', $salarie->id)->exists()) {
                continue;
            }

            $debut = now()->addDays(15 + ($i * 10));
            $reprise = $debut->copy()->addDays(14);

            try {
                $demande = $action->executer([
                    'salarie_id' => $salarie->id,
                    'type_conge_id' => $typeCongeAnnuel->id,
                    'date_debut' => $debut->format('Y-m-d'),
                    'date_reprise' => $reprise->format('Y-m-d'),
                    'motif' => 'Congé annuel ' . now()->year,
                    'remplacant' => $salaries->where('id', '!=', $salarie->id)->first()?->nom_complet,
                ]);

                // Faire évoluer le statut selon l'index
                match ($i) {
                    0 => $demande->update(['statut' => StatutDemandeConge::AUTORISEE->value, 'date_decision' => now()]),
                    1 => $demande->update(['statut' => StatutDemandeConge::SOUMISE->value]),
                    2 => $demande->update(['statut' => StatutDemandeConge::PROGRAMMEE->value]),
                    default => null,
                };

                // Resynchroniser le solde
                $annee = $debut->year;
                $solde = \App\Domain\Conges\Models\SoldeConge::where('salarie_id', $salarie->id)
                    ->where('type_conge_id', $typeCongeAnnuel->id)
                    ->where('annee', $annee)
                    ->first();
                if ($solde) {
                    app(\App\Domain\Conges\Services\CalculateurSolde::class)->resynchroniser($solde);
                }

                $compteur++;
            } catch (\Throwable $e) {
                $this->error("Erreur pour salarié {$salarie->id} : " . $e->getMessage());
            }
        }

        $this->command->info("{$compteur} demande(s) de congé(s) de démonstration créée(s).");
    }
}