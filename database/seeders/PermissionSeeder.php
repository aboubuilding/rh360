<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public static function catalogue(): array
    {
        return [
            'dashboard.view',

            'salaries.view', 'salaries.manage', 'salaries.validate',
            'salaries.import', 'salaries.export', 'salaries.fusion',

            'contrats.view', 'contrats.manage', 'contrats.validate', 'contrats.sign',

            'carriere.view', 'carriere.manage', 'carriere.validate',

            'conges.view', 'conges.manage', 'conges.validate',
            'conges.soldes.view', 'conges.soldes.manage',

            'paie.view', 'paie.manage', 'paie.calculer', 'paie.valider', 'paie.export',

            'formation.view', 'formation.manage', 'formation.validate',

            'performance.view', 'performance.manage', 'performance.evaluer',

            'recrutement.view', 'recrutement.manage',

            'health.view', 'health.manage',
            'safety.view', 'safety.manage',
            'risks.view', 'risks.manage',
            'ppe.view', 'ppe.manage',
            'habilitations.view', 'habilitations.manage',

            'sensitive.social_health',
            'sensitive.banking',
            'sensitive.gps',

            'organisation.view', 'organisation.manage',
            'classification.view', 'classification.manage',

            'admin.entreprise.view', 'admin.entreprise.manage',
            'admin.utilisateurs.view', 'admin.utilisateurs.manage',
            'admin.permissions.view', 'admin.permissions.manage',
            'admin.audit.view',

            'reports.personnel', 'reports.carriere', 'reports.paie',
            'reports.sst', 'reports.global',
        ];
    }

    public function run(): void {}
}