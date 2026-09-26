<?php

namespace Database\Seeders;

use App\Domain\Administration\Models\Entreprise;
use App\Domain\Administration\Models\Utilisateur;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class EntrepriseDemoSeeder extends Seeder
{
    public function run(): void
    {
        $entreprise = Entreprise::create([
            'nom' => 'EXPERT RH 360 Démo',
            'sigle' => 'ERH360',
            'forme_juridique' => 'SARL',
            'pays' => 'Togo',
            'ville' => 'Lomé',
            'devise' => 'XOF',
            'actif' => true,
            'etat' => 1,
        ]);

        Utilisateur::create([
            'entreprise_id' => $entreprise->id,
            'nom_complet' => 'Super Administrateur',
            'identifiant' => 'superadmin',
            'email' => 'superadmin@expert-rh360.test',
            'password' => Hash::make('Admin@2026!'),
            'role' => Utilisateur::ROLE_SUPER_ADMIN,
            'actif' => true,
            'etat' => 1,
        ]);

        $this->command->info('Entreprise démo créée. Identifiants : superadmin / Admin@2026!');
    }
}