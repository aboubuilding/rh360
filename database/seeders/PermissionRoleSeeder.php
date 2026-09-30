<?php

namespace Database\Seeders;

use App\Domain\Administration\Models\PermissionRole;
use App\Domain\Administration\Models\Utilisateur;
use Illuminate\Database\Seeder;

class PermissionRoleSeeder extends Seeder
{
    public function run(): void
    {
        $entrepriseId = 1;
        $toutes = PermissionSeeder::catalogue();

        PermissionRole::where('entreprise_id', $entrepriseId)->delete();

        $this->attribuer($entrepriseId, Utilisateur::ROLE_SUPER_ADMIN, $toutes);

        // Administrateur : paramétrage technique, sans accès aux données nominatives (CDC §3, §5).
        // Les permissions (matrice, exceptions) relèvent du Super administrateur et du DRH (CDC §4 8.4).
        $admin = [
            'dashboard.view',
            'admin.entreprise.view', 'admin.entreprise.manage',
            'admin.utilisateurs.view', 'admin.utilisateurs.manage',
            'admin.audit.view',
            'organisation.view', 'organisation.manage',
            'classification.view', 'classification.manage',
        ];
        $this->attribuer($entrepriseId, Utilisateur::ROLE_ADMIN, $admin);

        // DRH : tout le métier + gestion des permissions (matrice et exceptions individuelles) ;
        // consulte les comptes pour attribuer les exceptions mais ne les gère pas (CDC §4 8.1, 8.4).
        $drh = array_filter($toutes, fn ($p) => $p !== 'admin.utilisateurs.manage'
            && $p !== 'admin.entreprise.manage');
        $this->attribuer($entrepriseId, Utilisateur::ROLE_DRH, array_values($drh));

        $rh = [
            'dashboard.view',
            'admin.entreprise.view',
            'salaries.view', 'salaries.manage', 'salaries.import', 'salaries.export',
            // Préparation et référencement de la signature, sans validation (CDC §4 2.3, §5)
            'contrats.view', 'contrats.manage', 'contrats.sign',
            'carriere.view', 'carriere.manage',
            'conges.view', 'conges.manage', 'conges.soldes.view',
            'paie.view', 'paie.manage',
            'formation.view', 'formation.manage',
            'performance.view', 'performance.manage', 'performance.evaluer',
            'recrutement.view', 'recrutement.manage',
            'health.view', 'health.manage',
            'safety.view', 'safety.manage',
            'risks.view', 'risks.manage',
            'ppe.view', 'ppe.manage',
            'habilitations.view', 'habilitations.manage',
            'sensitive.social_health',
            // Gestion de l'organisation (CDC §4 8.2) ; grille salariale en consultation (CDC §4 8.3)
            'organisation.view', 'organisation.manage',
            'classification.view',
            'reports.personnel',
        ];
        $this->attribuer($entrepriseId, Utilisateur::ROLE_RH, $rh);

        $manager = [
            'dashboard.view',
            'admin.entreprise.view',
            'salaries.view',
            'conges.view',
            'formation.view',
            'performance.view', 'performance.manage', 'performance.evaluer',
            'recrutement.view',
            'organisation.view',
        ];
        $this->attribuer($entrepriseId, Utilisateur::ROLE_MANAGER, $manager);

        // Direction et Auditeur : SST en indicateurs agrégés uniquement (reports.sst),
        // jamais les registres nominatifs health/safety/risks/ppe/habilitations (CDC §4 7.1–7.5, §6).
        $direction = [
            'dashboard.view',
            'admin.entreprise.view',
            'salaries.view',
            'contrats.view',
            'carriere.view',
            'conges.view',
            'paie.view',
            'formation.view',
            'performance.view',
            'recrutement.view',
            'organisation.view',
            'classification.view',
            'reports.global', 'reports.personnel', 'reports.paie', 'reports.sst',
        ];
        $this->attribuer($entrepriseId, Utilisateur::ROLE_DIRECTION, $direction);

        $auditeur = [
            'dashboard.view',
            'admin.entreprise.view',
            'paie.view',
            'contrats.view',
            'admin.audit.view',
            'reports.global', 'reports.sst',
        ];
        $this->attribuer($entrepriseId, Utilisateur::ROLE_AUDITEUR, $auditeur);
    }

    private function attribuer(int $entrepriseId, string $role, array $permissions): void
    {
        foreach ($permissions as $permission) {
            PermissionRole::create([
                'entreprise_id' => $entrepriseId,
                'role' => $role,
                'permission' => $permission,
                'autorise' => true,
            ]);
        }
    }
}