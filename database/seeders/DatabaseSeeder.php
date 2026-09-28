<?php

namespace Database\Seeders;

use App\Models\User;
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
        $this->call([
           
        // Lot 0 — Socle
            EntrepriseDemoSeeder::class,
            PermissionRoleSeeder::class,

            // Lot 1 — Organisation & Classification
            TypeStructureSeeder::class,
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

        
        ]);
    }
}
