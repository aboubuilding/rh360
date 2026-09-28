<?php

namespace Database\Seeders;

use App\Domain\Personnel\Models\Salarie;
use App\Domain\Sst\Enums\AptitudeMedicale;
use App\Domain\Sst\Enums\CategorieEpi;
use App\Domain\Sst\Enums\FamilleRisque;
use App\Domain\Sst\Enums\NatureOperationEpi;
use App\Domain\Sst\Enums\NiveauRisque;
use App\Domain\Sst\Enums\StatutDotationEpi;
use App\Domain\Sst\Enums\StatutEvenementSecurite;
use App\Domain\Sst\Enums\StatutHabilitation;
use App\Domain\Sst\Enums\StatutVisiteMedicale;
use App\Domain\Sst\Enums\TypeEvenementSecurite;
use App\Domain\Sst\Enums\TypeVisiteMedicale;
use App\Domain\Sst\Models\ActionSecurite;
use App\Domain\Sst\Models\DotationEpi;
use App\Domain\Sst\Models\EvenementSecurite;
use App\Domain\Sst\Models\Habilitation;
use App\Domain\Sst\Models\OperationEpi;
use App\Domain\Sst\Models\Risque;
use App\Domain\Sst\Models\VisiteMedicale;
use App\Domain\Sst\Services\CalculateurScoreRisque;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SstDemoSeeder extends Seeder
{
    public function run(): void
    {
        $entrepriseId = 1;
        $superAdmin = \App\Domain\Administration\Models\Utilisateur::first();

        $salaries = Salarie::where('entreprise_id', $entrepriseId)
            ->where('actif', true)
            ->limit(5)
            ->get();

        if ($salaries->isEmpty()) {
            $this->command->warn('Aucun salarié. Exécutez SalariesDemoSeeder d\'abord.');
            return;
        }

        // ============================================================
        // VISITES MÉDICALES
        // ============================================================
        $compteurVisites = 0;
        foreach ($salaries as $i => $salarie) {
            if (VisiteMedicale::where('salarie_id', $salarie->id)->exists()) continue;

            VisiteMedicale::create([
                'entreprise_id' => $entrepriseId,
                'salarie_id' => $salarie->id,
                'type_visite' => $i % 2 === 0 ? TypeVisiteMedicale::EMBAUCHE->value : TypeVisiteMedicale::PERIODIQUE->value,
                'date_prevue' => now()->subMonths(6)->addDays($i * 10),
                'date_realisation' => now()->subMonths(6)->addDays($i * 10),
                'statut' => StatutVisiteMedicale::REALISEE->value,
                'aptitude' => $i === 4 ? AptitudeMedicale::APTE_AVEC_RESTRICTIONS->value : AptitudeMedicale::APTE->value,
                'prestataire' => 'Dr. Mensah',
                'reference_avis' => 'AVIS-' . now()->format('Y') . '-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'restrictions' => $i === 4 ? 'Pas de port de charges lourdes > 20 kg' : null,
                'date_prochaine_echeance' => now()->subMonths(6)->addDays($i * 10)->addYear(),
                'cree_par' => $superAdmin?->id ?? 1,
                'modifie_par' => $superAdmin?->id ?? 1,
                'revision' => 1,
                'etat' => 1,
            ]);
            $compteurVisites++;
        }

        // Programmer 2 visites à venir (pour tester les alertes)
        foreach ($salaries->take(2) as $i => $salarie) {
            VisiteMedicale::create([
                'entreprise_id' => $entrepriseId,
                'salarie_id' => $salarie->id,
                'type_visite' => TypeVisiteMedicale::PERIODIQUE->value,
                'date_prevue' => now()->addDays(15 - ($i * 5)),
                'statut' => StatutVisiteMedicale::PLANIFIEE->value,
                'aptitude' => AptitudeMedicale::EN_ATTENTE->value,
                'cree_par' => $superAdmin?->id ?? 1,
                'revision' => 1,
                'etat' => 1,
            ]);
            $compteurVisites++;
        }

        $this->command->info("{$compteurVisites} visites médicales créées.");

        // ============================================================
        // ÉVÉNEMENTS SÉCURITÉ
        // ============================================================
        $evenementsDemo = [
            [
                'type' => TypeEvenementSecurite::ACCIDENT_TRAVAIL,
                'intitule' => 'Chute sur sol glissant à l\'atelier',
                'localisation' => 'Atelier de production',
                'description' => 'Le salarié a glissé sur un sol mouillé non signalé.',
                'statut' => StatutEvenementSecurite::CLOTURE,
                'jours_avant' => 45,
            ],
            [
                'type' => TypeEvenementSecurite::PRESQUE_ACCIDENT,
                'intitule' => 'Câble électrique dénudé repéré',
                'localisation' => 'Zone de maintenance',
                'description' => 'Un câble dénudé a été repéré avant tout contact.',
                'statut' => StatutEvenementSecurite::EN_TRAITEMENT,
                'jours_avant' => 10,
            ],
            [
                'type' => TypeEvenementSecurite::INCIDENT,
                'intitule' => 'Déversement de produit chimique',
                'localisation' => 'Entrepôt',
                'description' => 'Un bidon d\'acide a fui légèrement.',
                'statut' => StatutEvenementSecurite::DECLARE,
                'jours_avant' => 3,
            ],
        ];

        $compteurEvenements = 0;
        foreach ($evenementsDemo as $i => $evt) {
            $evenement = EvenementSecurite::create([
                'entreprise_id' => $entrepriseId,
                'cle_soumission' => (string) Str::uuid(),
                'type_evenement' => $evt['type']->value,
                'date_survenance' => now()->subDays($evt['jours_avant']),
                'heure_survenance' => '10:' . str_pad($i * 15, 2, '0', STR_PAD_LEFT),
                'date_declaration' => now()->subDays($evt['jours_avant'])->addHours(2),
                'intitule' => $evt['intitule'],
                'localisation' => $evt['localisation'],
                'description' => $evt['description'],
                'mesures_immediates' => 'Zone isolée et sécurisée immédiatement.',
                'priorite' => $i === 0 ? 'high' : 'normal',
                'statut' => $evt['statut']->value,
                'statut_externe' => 'none',
                'date_cloture' => $evt['statut'] === StatutEvenementSecurite::CLOTURE ? now()->subDays($evt['jours_avant'] - 15) : null,
                'synthese_cloture' => $evt['statut'] === StatutEvenementSecurite::CLOTURE
                    ? 'Analyse terminée. Actions correctives mises en place.'
                    : null,
                'cree_par' => $superAdmin?->id ?? 1,
                'modifie_par' => $superAdmin?->id ?? 1,
                'revision' => 1,
                'etat' => 1,
            ]);

            // Attacher 1-2 participants
            $participants = $salaries->random(min(2, $salaries->count()))->pluck('id')->toArray();
            $evenement->participants()->attach($participants);

            // Ajouter une action corrective
            ActionSecurite::create([
                'entreprise_id' => $entrepriseId,
                'evenement_id' => $evenement->id,
                'cle_soumission' => (string) Str::uuid(),
                'intitule' => 'Mettre en place une signalisation adaptée',
                'responsable_salarie_id' => $salaries->first()->id,
                'date_echeance' => now()->addDays(15),
                'statut' => $i === 0 ? 'done' : 'todo',
                'date_realisation' => $i === 0 ? now()->subDays(20) : null,
                'resultat' => $i === 0 ? 'Signalisation installée' : null,
                'cree_par' => $superAdmin?->id ?? 1,
                'modifie_par' => $superAdmin?->id ?? 1,
                'revision' => 1,
                'etat' => 1,
            ]);

            $compteurEvenements++;
        }

        $this->command->info("{$compteurEvenements} événements sécurité créés.");

        // ============================================================
        // RISQUES
        // ============================================================
        $calculateur = app(CalculateurScoreRisque::class);

        $risquesDemo = [
            [
                'intitule' => 'Exposition au bruit en atelier',
                'famille' => FamilleRisque::PHYSIQUE,
                'site' => 'Site principal',
                'activite' => 'Utilisation de machines-outils',
                'danger' => 'Niveau sonore supérieur à 85 dB',
                'consequences' => 'Perte auditive progressive',
                'gravite' => 3,
                'probabilite' => 4,
            ],
            [
                'intitule' => 'Manutention manuelle de charges',
                'famille' => FamilleRisque::ERGONOMIQUE,
                'site' => 'Entrepôt',
                'activite' => 'Chargement/déchargement',
                'danger' => 'Port de charges > 25 kg répété',
                'consequences' => 'Lombalgies, TMS',
                'gravite' => 4,
                'probabilite' => 3,
            ],
            [
                'intitule' => 'Contact avec produits chimiques',
                'famille' => FamilleRisque::CHIMIQUE,
                'site' => 'Laboratoire',
                'activite' => 'Préparation de solutions',
                'danger' => 'Manipulation d\'acides et bases concentrés',
                'consequences' => 'Brûlures chimiques, irritations',
                'gravite' => 5,
                'probabilite' => 2,
            ],
        ];

        $compteurRisques = 0;
        foreach ($risquesDemo as $rd) {
            if (Risque::where('intitule', $rd['intitule'])->exists()) continue;

            $risque = Risque::create([
                'entreprise_id' => $entrepriseId,
                'cle_soumission' => (string) Str::uuid(),
                'intitule' => $rd['intitule'],
                'famille' => $rd['famille']->value,
                'site' => $rd['site'],
                'poste_id' => null,
                'activite' => $rd['activite'],
                'danger' => $rd['danger'],
                'consequences' => $rd['consequences'],
                'date_identification' => now()->subMonths(3),
                'responsable_salarie_id' => $salaries->first()->id,
                'date_echeance_revue' => now()->addMonths(3),
                'statut' => 'active',
                'revision_perimetre' => 1,
                'revision_mesures' => 1,
                'cree_par' => $superAdmin?->id ?? 1,
                'modifie_par' => $superAdmin?->id ?? 1,
                'revision' => 1,
                'etat' => 1,
            ]);

            // Évaluation initiale
            $calcul = $calculateur->calculer($rd['gravite'], $rd['probabilite']);

            \App\Domain\Sst\Models\EvaluationRisque::create([
                'entreprise_id' => $entrepriseId,
                'risque_id' => $risque->id,
                'cle_soumission' => (string) Str::uuid(),
                'date_evaluation' => now()->subMonths(3),
                'motif' => 'initial',
                'gravite' => $rd['gravite'],
                'probabilite' => $rd['probabilite'],
                'score' => $calcul['score'],
                'niveau' => $calcul['niveau']->value,
                'version_methode' => 'v1',
                'mesures_existantes' => 'Formation du personnel, EPI fournis',
                'justification' => 'Évaluation initiale à la suite de la revue annuelle',
                'date_prochaine_revue' => now()->addMonths(3),
                'revision_perimetre' => 1,
                'revision_mesures' => 1,
                'instantane_contexte' => [
                    'perimetre_revision' => 1,
                    'mesures_revision' => 1,
                    'site' => $rd['site'],
                    'activite' => $rd['activite'],
                ],
                'cree_par' => $superAdmin?->id ?? 1,
                'etat' => 1,
            ]);

            $compteurRisques++;
        }

        $this->command->info("{$compteurRisques} risques créés.");

        // ============================================================
        // DOTATIONS EPI
        // ============================================================
        $compteurEpi = 0;
        foreach ($salaries->take(3) as $i => $salarie) {
            if (DotationEpi::where('salarie_id', $salarie->id)->exists()) continue;

            $dotation = DotationEpi::create([
                'entreprise_id' => $entrepriseId,
                'salarie_id' => $salarie->id,
                'risque_id' => null,
                'cle_soumission' => (string) Str::uuid(),
                'categorie' => $i === 0 ? CategorieEpi::TETE->value : ($i === 1 ? CategorieEpi::MAINS->value : CategorieEpi::PIEDS->value),
                'intitule' => $i === 0 ? 'Casque de chantier' : ($i === 1 ? 'Gants de protection nitrile' : 'Chaussures de sécurité S3'),
                'quantite' => 1,
                'unite' => 'unité',
                'numero_serie' => 'EPI-' . now()->format('Y') . '-' . str_pad($i + 1, 5, '0', STR_PAD_LEFT),
                'taille' => $i === 2 ? '42' : null,
                'date_remise' => now()->subMonths(6),
                'date_expiration' => now()->addMonths(6),
                'date_verification' => now()->addMonths(1),
                'emetteur' => 'Service HSE',
                'reference_recu' => 'RECU-' . now()->format('Y') . '-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'statut' => StatutDotationEpi::EN_USAGE->value,
                'cree_par' => $superAdmin?->id ?? 1,
                'modifie_par' => $superAdmin?->id ?? 1,
                'revision' => 1,
                'etat' => 1,
            ]);

            $compteurEpi++;
        }

        $this->command->info("{$compteurEpi} dotations EPI créées.");

        // ============================================================
        // HABILITATIONS
        // ============================================================
        $compteurHab = 0;
        foreach ($salaries->take(2) as $i => $salarie) {
            if (Habilitation::where('salarie_id', $salarie->id)->exists()) continue;

            Habilitation::create([
                'entreprise_id' => $entrepriseId,
                'salarie_id' => $salarie->id,
                'risque_id' => null,
                'cle_soumission' => (string) Str::uuid(),
                'categorie' => $i === 0 ? 'Électrique' : 'Travaux en hauteur',
                'intitule' => $i === 0 ? 'Habilitation électrique B1V' : 'Travaux en hauteur — catégorie 1',
                'portee' => $i === 0
                    ? 'Interventions sur installations électriques basse tension'
                    : 'Travaux sur échafaudages et toitures',
                'emetteur' => 'APAVE',
                'reference_decision' => 'HAB-' . now()->format('Y') . '-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'reference_formation' => 'FOR-HAB-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'date_decision' => now()->subMonths(10),
                'date_debut' => now()->subMonths(10),
                'date_fin' => now()->addMonths(14),
                'date_revue' => now()->addMonths(12),
                'statut' => StatutHabilitation::ACTIVE->value,
                'cree_par' => $superAdmin?->id ?? 1,
                'modifie_par' => $superAdmin?->id ?? 1,
                'revision' => 1,
                'etat' => 1,
            ]);

            $compteurHab++;
        }

        $this->command->info("{$compteurHab} habilitations créées.");

        $this->command->info('====== Seeder SST terminé ======');
    }
}