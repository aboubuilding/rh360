<?php

namespace Database\Seeders;

use App\Domain\Organisation\Models\TypeStructure;
use Illuminate\Database\Seeder;

class TypeStructureSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['code' => 'DG', 'nom' => 'Direction générale', 'ordre' => 1],
            ['code' => 'DIR', 'nom' => 'Direction', 'ordre' => 2],
            ['code' => 'DEP', 'nom' => 'Département', 'ordre' => 3],
            ['code' => 'DIV', 'nom' => 'Division', 'ordre' => 4],
            ['code' => 'SER', 'nom' => 'Service', 'ordre' => 5],
        ];

        foreach ($types as $type) {
            TypeStructure::updateOrCreate(
                ['entreprise_id' => 1, 'code' => $type['code']],
                array_merge($type, ['entreprise_id' => 1, 'actif' => true, 'etat' => 1])
            );
        }
    }
}