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

        $admin = array_filter($toutes, fn ($p) => ! str_starts_with($p, 'salaries.')
            && ! str_starts_with($p, 'health.')
            && ! str_starts_with($p, 'safety.')
            && ! str_starts_with($p, 'risks.')
            && ! str_starts_with($p, 'ppe.')
            && ! str_starts_with($p, 'habilitations.')
            && ! str_starts_with($p, 'sensitive.'));
        $this->attribuer($entrepriseId, Utilisateur::ROLE_ADMIN, array_values($admin));

        $drh = array_filter($toutes, fn ($p) => ! str_starts_with($p, 'admin.utilisateurs.')
            && ! str_starts_with($p, 'admin.entreprise.')
            && ! str_starts_with($p, 'admin.permissions.'));
        $this->attribuer($entrepriseId, Utilisateur::ROLE_DRH, array_values($drh));

        $rh = [
            'dashboard.view',
            'salaries.view', 'salaries.manage', 'salaries.import', 'salaries.export',
            'contrats.view', 'contrats.manage',
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
            'organisation.view',
            'classification.view',
            'reports.personnel',
        ];
        $this->attribuer($entrepriseId, Utilisateur::ROLE_RH, $rh);

        $manager = [
            'dashboard.view',
            'salaries.view',
            'conges.view',
            'formation.view',
            'performance.view', 'performance.manage', 'performance.evaluer',
            'recrutement.view',
            'organisation.view',
        ];
        $this->attribuer($entrepriseId, Utilisateur::ROLE_MANAGER, $manager);

        $direction = [
            'dashboard.view',
            'salaries.view',
            'contrats.view',
            'carriere.view',
            'conges.view',
            'paie.view',
            'formation.view',
            'performance.view',
            'recrutement.view',
            'health.view',
            'safety.view',
            'risks.view',
            'ppe.view',
            'habilitations.view',
            'organisation.view',
            'classification.view',
            'reports.global', 'reports.personnel', 'reports.paie', 'reports.sst',
        ];
        $this->attribuer($entrepriseId, Utilisateur::ROLE_DIRECTION, $direction);

        $auditeur = [
            'dashboard.view',
            'paie.view',
            'contrats.view',
            'admin.audit.view',
            'health.view',
            'safety.view',
            'risks.view',
            'reports.global',
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