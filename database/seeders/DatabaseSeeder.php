<?php

namespace Database\Seeders;

use App\Domain\Administration\Models\Utilisateur;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Lot 0 — Socle
        $this->call([
            EntrepriseDemoSeeder::class,
            PermissionRoleSeeder::class,
        ]);

        // Les actions métier utilisées par les seeders de démo lisent l'entreprise
        // de l'utilisateur connecté : on agit au nom du super administrateur.
        $superAdmin = Utilisateur::where('role', Utilisateur::ROLE_SUPER_ADMIN)->first();
        if ($superAdmin) {
            auth()->setUser($superAdmin);
        }

        $this->call([
            // Lot 1 — Organisation & Classification
            TypeStructureSeeder::class,
            OrganisationDemoSeeder::class,
            ReferentielClassificationSeeder::class,

            // Lot 2 — Personnel
            SalariesDemoSeeder::class,

            // Lot 3 — Contrats
            ParametresContratsSeeder::class,
            ReglesContratsSeeder::class,
            ContratsDemoSeeder::class,

            // Lot 4 — Carrière
            CarriereDemoSeeder::class,

            // Lot 5 — Congés & Absences
            TypesCongesSeeder::class,
            CongesDemoSeeder::class,

            // Lot 6 — Paie
            ReglesPaieSeeder::class,
            RubriquesPaieSeeder::class,
            ModelesPaieSeeder::class,
            PeriodesPaieDemoSeeder::class,

            // Lot 7 — SST
            SstDemoSeeder::class,

            // Lot 8 — Développement RH
            FormationDemoSeeder::class,
            PerformanceDemoSeeder::class,
            RecrutementDemoSeeder::class,
        ]);

        auth()->forgetUser();
    }
}
