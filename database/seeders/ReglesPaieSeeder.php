<?php

namespace Database\Seeders;

use App\Domain\Paie\Models\RegleAnciennete;
use App\Domain\Paie\Models\RegleCotisation;
use App\Domain\Paie\Models\RegleIrpp;
use Illuminate\Database\Seeder;

class ReglesPaieSeeder extends Seeder
{
    public function run(): void
    {
        $entrepriseId = 1;
        $debutEffet = now()->startOfYear();

        // ============================================================
        // COTISATIONS SOCIALES (Togo — barèmes indicatifs)
        // ============================================================
        $cotisations = [
            [
                'code' => 'CNSS',
                'nom' => 'Caisse Nationale de Sécurité Sociale',
                'taux_salarial' => 4.0,
                'taux_patronal' => 15.0,
                'reference_legale' => 'Code de la sécurité sociale (Togo)',
            ],
            [
                'code' => 'AMU',
                'nom' => 'Assurance Maladie Universelle',
                'taux_salarial' => 5.0,
                'taux_patronal' => 5.0,
                'reference_legale' => 'Loi AMU (Togo)',
            ],
        ];

        foreach ($cotisations as $c) {
            RegleCotisation::updateOrCreate(
                ['entreprise_id' => $entrepriseId, 'code' => $c['code'], 'debut_effet' => $debutEffet],
                array_merge($c, [
                    'entreprise_id' => $entrepriseId,
                    'debut_effet' => $debutEffet,
                    'fin_effet' => null,
                    'actif' => true,
                    'etat' => 1,
                ])
            );
        }

        // ============================================================
        // PRIME D'ANCIENNETÉ
        // ============================================================
        RegleAnciennete::updateOrCreate(
            ['entreprise_id' => $entrepriseId, 'debut_effet' => $debutEffet],
            [
                'entreprise_id' => $entrepriseId,
                'annees_min' => 2,
                'taux_initial' => 3.0,
                'increment_annuel' => 1.0,
                'taux_max' => 15.0,
                'mode_base' => 'salary_base',
                'debut_effet' => $debutEffet,
                'fin_effet' => null,
                'reference_legale' => 'Convention collective interprofessionnelle',
                'actif' => true,
                'etat' => 1,
            ]
        );

        // ============================================================
        // BARÈME IRPP PROGRESSIF (Togo)
        // ============================================================
        RegleIrpp::updateOrCreate(
            ['entreprise_id' => $entrepriseId, 'debut_effet' => $debutEffet],
            [
                'entreprise_id' => $entrepriseId,
                'debut_effet' => $debutEffet,
                'fin_effet' => null,
                'reference_legale' => 'Code général des impôts (Togo)',
                'taux_abattement_professionnel' => 28.0,
                'plafond_abattement_professionnel' => 10000000,
                'deduction_mensuelle_par_charge' => 10000,
                'nombre_max_charges' => 6,
                'tranches' => [600000, 1200000, 2400000, 3600000, 5000000, 10000000],
                'taux_tranches' => [0, 5, 10, 15, 20, 25],
                'actif' => true,
                'etat' => 1,
            ]
        );

        $this->command->info('Règles de paie (cotisations, ancienneté, IRPP) créées.');
    }
}