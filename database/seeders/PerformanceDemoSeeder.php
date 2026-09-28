<?php

namespace Database\Seeders;

use App\Domain\Performance\Enums\FamilleCritere;
use App\Domain\Performance\Enums\StatutCampagne;
use App\Domain\Performance\Enums\StatutEntretien;
use App\Domain\Performance\Enums\StatutObjectif;
use App\Domain\Performance\Models\CampagneEvaluation;
use App\Domain\Performance\Models\CritereEvaluation;
use App\Domain\Performance\Models\EntretienEvaluation;
use App\Domain\Performance\Models\ObjectifEvaluation;
use App\Domain\Personnel\Models\Salarie;
use Illuminate\Database\Seeder;

class PerformanceDemoSeeder extends Seeder
{
    public function run(): void
    {
        $entrepriseId = 1;

        $campagne = CampagneEvaluation::updateOrCreate(
            ['entreprise_id' => $entrepriseId, 'intitule' => 'Campagne ' . now()->year],
            [
                'entreprise_id' => $entrepriseId,
                'annee' => now()->year,
                'date_debut' => now()->startOfYear(),
                'date_fin' => now()->addMonths(3),
                'statut' => StatutCampagne::EN_COURS->value,
                'etat' => 1,
            ]
        );

        // 4 critères
        $criteres = [
            ['code' => 'CR-COMP', 'libelle' => 'Compétence technique', 'famille' => FamilleCritere::COMPETENCE->value, 'ponderation' => 3],
            ['code' => 'CR-RES', 'libelle' => 'Atteinte des résultats', 'famille' => FamilleCritere::RESULTAT->value, 'ponderation' => 4],
            ['code' => 'CR-COM', 'libelle' => 'Communication', 'famille' => FamilleCritere::COMPORTEMENT->value, 'ponderation' => 2],
            ['code' => 'CR-POT', 'libelle' => 'Potentiel d\'évolution', 'famille' => FamilleCritere::POTENTIEL->value, 'ponderation' => 1],
        ];

        foreach ($criteres as $c) {
            CritereEvaluation::updateOrCreate(
                ['entreprise_id' => $entrepriseId, 'code' => $c['code']],
                array_merge($c, ['entreprise_id' => $entrepriseId, 'actif' => true, 'etat' => 1])
            );
        }

        $salaries = Salarie::where('entreprise_id', $entrepriseId)->where('actif', true)->limit(4)->get();

        if ($salaries->isEmpty()) {
            $this->command->warn('Aucun salarié.');
            return;
        }

        // Créer 4 entretiens avec des statuts variés + objectifs
        foreach ($salaries as $i => $salarie) {
            $statut = match ($i) {
                0 => StatutEntretien::VALIDE->value,
                1 => StatutEntretien::REALISE->value,
                2 => StatutEntretien::AUTO_EVALUE->value,
                default => StatutEntretien::A_PREPARER->value,
            };

            $entretien = EntretienEvaluation::updateOrCreate(
                ['campagne_id' => $campagne->id, 'salarie_id' => $salarie->id],
                [
                    'entreprise_id' => $entrepriseId,
                    'statut' => $statut,
                    'note_auto_evaluation' => $i < 3 ? 15 : null,
                    'note_manager' => $i < 2 ? 16 : null,
                    'note_finale' => $i < 2 ? 15.8 : null,
                    'date_entretien' => $i < 2 ? now()->subDays(15) : null,
                    'points_forts' => $i < 2 ? 'Rigueur, autonomie, sens du service' : null,
                    'besoins_developpement' => $i < 2 ? 'Renforcer les compétences en gestion de projet' : null,
                    'action_amelioration' => $i === 0 ? 'Suivre la formation Management d\'équipe' : null,
                    'date_echeance_amelioration' => $i === 0 ? now()->addMonths(6) : null,
                    'etat' => 1,
                ]
            );

            // 2 objectifs par entretien
            if ($i < 3) {
                ObjectifEvaluation::updateOrCreate(
                    ['campagne_id' => $campagne->id, 'salarie_id' => $salarie->id, 'intitule' => 'Objectif annuel #1'],
                    [
                        'entreprise_id' => $entrepriseId,
                        'indicateur' => 'Nombre de dossiers traités',
                        'cible' => '50 dossiers',
                        'ponderation' => 2,
                        'date_echeance' => now()->endOfYear(),
                        'statut' => StatutObjectif::EN_COURS->value,
                        'etat' => 1,
                    ]
                );
                ObjectifEvaluation::updateOrCreate(
                    ['campagne_id' => $campagne->id, 'salarie_id' => $salarie->id, 'intitule' => 'Objectif annuel #2'],
                    [
                        'entreprise_id' => $entrepriseId,
                        'indicateur' => 'Satisfaction client interne',
                        'cible' => '> 90 %',
                        'ponderation' => 1.5,
                        'date_echeance' => now()->endOfYear(),
                        'statut' => StatutObjectif::A_REALISER->value,
                        'etat' => 1,
                    ]
                );
            }
        }

        $this->command->info('Performance : 1 campagne, 4 critères, 4 entretiens et objectifs créés.');
    }
}