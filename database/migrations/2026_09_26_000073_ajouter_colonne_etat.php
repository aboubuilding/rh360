<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute la colonne « etat » (1=actif, 0=inactif, -1=supprime) utilisée par le trait
 * App\Domain\Shared\Traits\AvecEtat sur toutes les tables métier, ainsi que le
 * rattachement optionnel des critères d'évaluation à une campagne.
 * Les migrations de reprise (000001 à 000072) ne portent pas ces colonnes.
 */
return new class extends Migration
{
    private const TABLES = [
        'absences',
        'actions_risques',
        'actions_securite',
        'affectations',
        'alertes_contrats',
        'besoins_formation',
        'besoins_recrutement',
        'brouillons_salaries',
        'bulletins_paie',
        'campagnes_evaluation',
        'candidats',
        'categories_classification',
        'classes_classification',
        'contrats',
        'criteres_evaluation',
        'demandes_conges',
        'documents_salaries',
        'dossiers_maternite',
        'dotations_epi',
        'echelons_classification',
        'elements_paie_salaries',
        'entreprises',
        'entretiens_evaluation',
        'evaluations_risques',
        'evenements_essai',
        'evenements_securite',
        'formations',
        'habilitations',
        'heures_supplementaires',
        'membres_foyer',
        'modeles_paie',
        'modeles_paie_rubriques',
        'mouvements_carriere',
        'objectifs_evaluation',
        'operations_epi',
        'parametres_contrats',
        'participants_formation',
        'periodes_paie',
        'plan_formation',
        'positions_classification',
        'postes',
        'referentiels_classification',
        'regles_anciennete',
        'regles_contrats',
        'regles_cotisations',
        'regles_evolution',
        'regles_irpp',
        'risques',
        'rubriques_paie',
        'salaries',
        'sessions_formation',
        'situations_carriere',
        'soldes_conges',
        'structures',
        'types_conges',
        'types_structures',
        'utilisateurs',
        'visites_medicales',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $nomTable) {
            if (Schema::hasTable($nomTable) && ! Schema::hasColumn($nomTable, 'etat')) {
                Schema::table($nomTable, function (Blueprint $table) {
                    $table->tinyInteger('etat')->default(1)->index()->comment('1=actif, 0=inactif, -1=supprime');
                });
            }
        }

        if (! Schema::hasColumn('criteres_evaluation', 'campagne_id')) {
            Schema::table('criteres_evaluation', function (Blueprint $table) {
                $table->foreignId('campagne_id')->nullable()->after('entreprise_id')
                    ->constrained('campagnes_evaluation')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('criteres_evaluation', 'campagne_id')) {
            Schema::table('criteres_evaluation', function (Blueprint $table) {
                $table->dropConstrainedForeignId('campagne_id');
            });
        }

        foreach (self::TABLES as $nomTable) {
            if (Schema::hasTable($nomTable) && Schema::hasColumn($nomTable, 'etat')) {
                Schema::table($nomTable, function (Blueprint $table) {
                    $table->dropIndex(['etat']);
                    $table->dropColumn('etat');
                });
            }
        }
    }
};
