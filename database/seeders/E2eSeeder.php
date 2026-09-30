<?php

namespace Database\Seeders;

use App\Domain\Administration\Models\Utilisateur;
use Illuminate\Database\Seeder;

/**
 * Comptes utilisés par les tests E2E Playwright (tests/e2e) : un compte par profil
 * du cahier des charges (§3), tous rattachés à l'entreprise de démonstration.
 * À n'exécuter que sur la base dédiée aux tests E2E.
 */
class E2eSeeder extends Seeder
{
    public const MOT_DE_PASSE = 'E2e@2026!';

    public const COMPTES = [
        'e2e_admin' => [Utilisateur::ROLE_ADMIN, 'Admin E2E'],
        'e2e_drh' => [Utilisateur::ROLE_DRH, 'DRH E2E'],
        'e2e_rh' => [Utilisateur::ROLE_RH, 'Responsable RH E2E'],
        'e2e_manager' => [Utilisateur::ROLE_MANAGER, 'Manager E2E'],
        'e2e_direction' => [Utilisateur::ROLE_DIRECTION, 'Direction E2E'],
        'e2e_auditeur' => [Utilisateur::ROLE_AUDITEUR, 'Auditeur E2E'],
    ];

    public function run(): void
    {
        foreach (self::COMPTES as $identifiant => [$role, $nom]) {
            Utilisateur::withoutGlobalScopes()->updateOrCreate(
                ['identifiant' => $identifiant],
                [
                    'entreprise_id' => 1,
                    'nom_complet' => $nom,
                    'email' => "{$identifiant}@expert-rh360.test",
                    'password' => self::MOT_DE_PASSE,
                    'role' => $role,
                    'actif' => true,
                    'etat' => 1,
                ]
            );
        }
    }
}
