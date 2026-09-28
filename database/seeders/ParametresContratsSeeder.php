<?php

namespace Database\Seeders;

use App\Domain\Contrats\Models\ParametreContrat;
use Illuminate\Database\Seeder;

class ParametresContratsSeeder extends Seeder
{
    public function run(): void
    {
        ParametreContrat::updateOrCreate(
            ['entreprise_id' => 1],
            [
                'seuils' => '30,15,7,0',
                'roles' => 'rh,drh',
                'revision' => 1,
                'etat' => 1,
            ]
        );

        $this->command->info('Paramètres contrats par défaut créés (seuils : 30,15,7,0 — rôles : rh,drh).');
    }
}