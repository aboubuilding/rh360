<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Ce seeder ne crée rien en base (les familles de risques sont portées
 * par l'énumération PHP FamilleRisque). Il sert d'ancrage pour une
 * éventuelle table de référentiel métier à venir.
 */
class CategoriesRisquesSeeder extends Seeder
{
    public function run(): void
    {
        // Les familles de risques sont dans l'enum FamilleRisque.
        $this->command->info('Familles de risques disponibles : ' . implode(', ', array_column(\App\Domain\Sst\Enums\FamilleRisque::cases(), 'value')));
    }
}