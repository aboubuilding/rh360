<?php

namespace Database\Seeders;

use App\Domain\Paie\Enums\StatutPeriode;
use App\Domain\Paie\Models\ElementPaieSalarie;
use App\Domain\Paie\Models\PeriodePaie;
use App\Domain\Paie\Models\RubriquePaie;
use App\Domain\Personnel\Models\Salarie;
use Illuminate\Database\Seeder;

class PeriodesPaieDemoSeeder extends Seeder
{
    public function run(): void
    {
        $entrepriseId = 1;

        $salaries = Salarie::where('entreprise_id', $entrepriseId)
            ->where('actif', true)
            ->get();

        if ($salaries->isEmpty()) {
            $this->command->warn('Aucun salarié. Exécutez SalariesDemoSeeder d\'abord.');
            return;
        }

        // Récupérer la rubrique salaire de base
        $salaireBase = RubriquePaie::where('entreprise_id', $entrepriseId)
            ->where('code', 'SAL-BASE')
            ->first();

        if (! $salaireBase) {
            $this->command->warn('Rubrique SAL-BASE absente. Exécutez RubriquesPaieSeeder d\'abord.');
            return;
        }

        // Créer un élément fixe "salaire de base" pour chaque salarié (salaire fictif)
        foreach ($salaries as $i => $salarie) {
            $montantSalaire = 200000 + ($i * 25000);

            ElementPaieSalarie::updateOrCreate(
                ['salarie_id' => $salarie->id, 'rubrique_id' => $salaireBase->id],
                [
                    'entreprise_id' => $entrepriseId,
                    'montant' => $montantSalaire,
                    'actif' => true,
                    'observations' => 'Salaire de base défini par le seeder de démonstration',
                    'etat' => 1,
                ]
            );
        }

        // Ouvrir 2 périodes de démonstration (mois précédent et mois courant)
        $moisPrecedent = now()->subMonth();
        $periodePrecedente = PeriodePaie::updateOrCreate(
            [
                'entreprise_id' => $entrepriseId,
                'annee' => $moisPrecedent->year,
                'mois' => $moisPrecedent->month,
            ],
            [
                'statut' => StatutPeriode::VALIDEE->value,
                'valide_le' => $moisPrecedent->copy()->endOfMonth(),
                'valide_par' => \App\Domain\Administration\Models\Utilisateur::first()?->id,
                'etat' => 1,
            ]
        );

        $periodeCourante = PeriodePaie::updateOrCreate(
            [
                'entreprise_id' => $entrepriseId,
                'annee' => now()->year,
                'mois' => now()->month,
            ],
            [
                'statut' => StatutPeriode::OUVERTE->value,
                'etat' => 1,
            ]
        );

        $this->command->info("Éléments fixes créés pour " . $salaries->count() . " salariés.");
        $this->command->info("Périodes créées : {$periodePrecedente->libelle} (validée) et {$periodeCourante->libelle} (ouverte).");
        $this->command->info("Pour calculer la paie courante : php artisan rh:calculer-paie-mensuelle");
    }
}