<?php

namespace Database\Seeders;

use App\Domain\Formation\Enums\ModaliteFormation;
use App\Domain\Formation\Enums\PrioriteBesoinFormation;
use App\Domain\Formation\Enums\StatutBesoinFormation;
use App\Domain\Formation\Enums\StatutPresenceParticipant;
use App\Domain\Formation\Enums\StatutSessionFormation;
use App\Domain\Formation\Models\BesoinFormation;
use App\Domain\Formation\Models\Formation;
use App\Domain\Formation\Models\ParticipantFormation;
use App\Domain\Formation\Models\PlanFormation;
use App\Domain\Formation\Models\SessionFormation;
use App\Domain\Personnel\Models\Salarie;
use Illuminate\Database\Seeder;

class FormationDemoSeeder extends Seeder
{
    public function run(): void
    {
        $entrepriseId = 1;
        $superAdmin = \App\Domain\Administration\Models\Utilisateur::first();

        // Catalogue de formations
        $formations = [
            ['code' => 'HSE-001', 'intitule' => 'Sécurité au poste de travail', 'domaine' => 'Sécurité', 'duree_heures' => 8, 'modalite' => ModaliteFormation::PRESENTIEL->value],
            ['code' => 'MAN-001', 'intitule' => 'Management d\'équipe', 'domaine' => 'Management', 'duree_heures' => 16, 'modalite' => ModaliteFormation::MIXTE->value],
            ['code' => 'INFO-001', 'intitule' => 'Bureautique avancée (Excel)', 'domaine' => 'Informatique', 'duree_heures' => 14, 'modalite' => ModaliteFormation::DISTANCIEL->value],
            ['code' => 'COM-001', 'intitule' => 'Communication interpersonnelle', 'domaine' => 'Développement personnel', 'duree_heures' => 7, 'modalite' => ModaliteFormation::PRESENTIEL->value],
        ];

        foreach ($formations as $f) {
            Formation::updateOrCreate(
                ['entreprise_id' => $entrepriseId, 'code' => $f['code']],
                array_merge($f, ['entreprise_id' => $entrepriseId, 'actif' => true, 'etat' => 1])
            );
        }

        // Plan de formation annuel
        $plan = PlanFormation::updateOrCreate(
            ['entreprise_id' => $entrepriseId, 'annee' => now()->year],
            [
                'entreprise_id' => $entrepriseId,
                'intitule' => 'Plan de formation ' . now()->year,
                'debut_prevu' => now()->startOfYear(),
                'montant_budget' => 5000000,
                'statut' => 'valide',
                'etat' => 1,
            ]
        );

        $salaries = Salarie::where('entreprise_id', $entrepriseId)->where('actif', true)->limit(5)->get();

        if ($salaries->isEmpty()) {
            $this->command->warn('Aucun salarié. Exécutez SalariesDemoSeeder d\'abord.');
            return;
        }

        // 3 besoins
        $besoins = [
            ['intitule' => 'Formation Excel niveau avancé', 'priorite' => PrioriteBesoinFormation::HAUTE->value, 'salarie_idx' => 0],
            ['intitule' => 'Sensibilisation sécurité', 'priorite' => PrioriteBesoinFormation::CRITIQUE->value, 'salarie_idx' => null],
            ['intitule' => 'Management de proximité', 'priorite' => PrioriteBesoinFormation::NORMALE->value, 'salarie_idx' => 1],
        ];

        foreach ($besoins as $b) {
            BesoinFormation::updateOrCreate(
                ['entreprise_id' => $entrepriseId, 'intitule' => $b['intitule']],
                [
                    'entreprise_id' => $entrepriseId,
                    'salarie_id' => $b['salarie_idx'] !== null ? ($salaries[$b['salarie_idx']]->id ?? null) : null,
                    'motif' => 'Besoin exprimé en entretien annuel',
                    'priorite' => $b['priorite'],
                    'annee_cible' => now()->year,
                    'statut' => StatutBesoinFormation::VALIDE->value,
                    'cree_par' => $superAdmin?->id ?? 1,
                    'etat' => 1,
                ]
            );
        }

        // 2 sessions
        $sessions = [
            [
                'intitule' => 'Sécurité au poste — session ' . now()->format('m/Y'),
                'prestataire' => 'APAVE',
                'date_debut' => now()->subDays(30),
                'date_fin' => now()->subDays(28),
                'duree_heures' => 16,
                'cout_reel' => 450000,
                'statut' => StatutSessionFormation::TERMINEE->value,
            ],
            [
                'intitule' => 'Excel avancé — session ' . now()->format('m/Y'),
                'prestataire' => 'Formation Pro',
                'date_debut' => now()->addDays(15),
                'date_fin' => now()->addDays(17),
                'duree_heures' => 14,
                'cout_reel' => 350000,
                'statut' => StatutSessionFormation::PROGRAMMEE->value,
            ],
        ];

        foreach ($sessions as $i => $s) {
            $session = SessionFormation::updateOrCreate(
                ['entreprise_id' => $entrepriseId, 'intitule' => $s['intitule']],
                array_merge($s, [
                    'entreprise_id' => $entrepriseId,
                    'plan_formation_id' => $plan->id,
                    'localisation' => 'Salle de formation — Siège',
                    'etat' => 1,
                ])
            );

            // Participants sur la 1re session (terminée)
            if ($i === 0) {
                foreach ($salaries as $j => $salarie) {
                    ParticipantFormation::updateOrCreate(
                        ['session_formation_id' => $session->id, 'salarie_id' => $salarie->id],
                        [
                            'entreprise_id' => $entrepriseId,
                            'statut_presence' => StatutPresenceParticipant::PRESENT->value,
                            'score_avant' => 40 + ($j * 5),
                            'score_apres' => 70 + ($j * 5),
                            'note_satisfaction' => 4.5,
                            'commentaire_evaluation' => 'Bonne session, contenu adapté.',
                            'reference_attestation' => 'ATT-SEC-' . str_pad($j + 1, 4, '0', STR_PAD_LEFT),
                            'etat' => 1,
                        ]
                    );
                }
            }

            // 2 participants sur la 2e session
            if ($i === 1) {
                foreach ($salaries->take(2) as $salarie) {
                    ParticipantFormation::updateOrCreate(
                        ['session_formation_id' => $session->id, 'salarie_id' => $salarie->id],
                        [
                            'entreprise_id' => $entrepriseId,
                            'statut_presence' => StatutPresenceParticipant::INSCRIT->value,
                            'etat' => 1,
                        ]
                    );
                }
            }
        }

        $this->command->info('Formation : 4 formations, 1 plan, 3 besoins, 2 sessions et participants créés.');
    }
}