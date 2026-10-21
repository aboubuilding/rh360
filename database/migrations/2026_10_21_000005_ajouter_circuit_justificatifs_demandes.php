<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Demandes et autorisations — circuit (colonnes additives).
 * - demandes_conges : force majeure, événement et membre du foyer concernés, échéance du
 *   justificatif, validation automatique, absence constatée régularisée, requalification ;
 * - pieces_demande : justificatifs déposés et leur contrôle ;
 * - historique_demandes : changements de statut, en insertion seule.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('demandes_conges', function (Blueprint $table) {
            $table->boolean('force_majeure')->default(false)->after('motif');
            $table->text('motif_force_majeure')->nullable()->after('force_majeure');
            $table->foreignId('evenement_permission_id')->nullable()->after('type_conge_id')->constrained('evenements_permission')->nullOnDelete();
            $table->date('date_evenement')->nullable()->after('evenement_permission_id');
            $table->foreignId('membre_foyer_id')->nullable()->after('date_evenement')->constrained('membres_foyer')->nullOnDelete();
            $table->date('echeance_justificatif')->nullable()->after('date_reprise');
            $table->boolean('validation_automatique')->default(false)->after('valide_par');
            $table->foreignId('absence_id')->nullable()->after('validation_automatique')->constrained('absences')->nullOnDelete();
            $table->string('requalification', 40)->nullable()->after('absence_id');
            $table->timestamp('requalifiee_le')->nullable()->after('requalification');
        });

        Schema::create('pieces_demande', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id')->constrained('entreprises')->restrictOnDelete();
            $table->foreignId('demande_id')->constrained('demandes_conges')->cascadeOnDelete();
            $table->string('nature', 160);
            $table->string('chemin');
            $table->string('nom_original')->nullable();
            $table->timestamp('recue_le');
            $table->boolean('conforme')->nullable();
            $table->string('motif_non_conforme', 500)->nullable();
            $table->foreignId('controle_par')->nullable()->constrained('utilisateurs')->nullOnDelete();
            $table->timestamp('controle_le')->nullable();
            $table->foreignId('depose_par')->nullable()->constrained('utilisateurs')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('historique_demandes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id')->constrained('entreprises')->restrictOnDelete();
            $table->foreignId('demande_id')->constrained('demandes_conges')->cascadeOnDelete();
            $table->string('de_statut', 40)->nullable();
            $table->string('vers_statut', 40);
            $table->boolean('automatique')->default(false);
            $table->foreignId('acteur_id')->nullable()->constrained('utilisateurs')->nullOnDelete();
            $table->text('motif')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['demande_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historique_demandes');
        Schema::dropIfExists('pieces_demande');

        Schema::table('demandes_conges', function (Blueprint $table) {
            $table->dropConstrainedForeignId('evenement_permission_id');
            $table->dropConstrainedForeignId('membre_foyer_id');
            $table->dropConstrainedForeignId('absence_id');
            $table->dropColumn([
                'force_majeure', 'motif_force_majeure', 'date_evenement', 'echeance_justificatif',
                'validation_automatique', 'requalification', 'requalifiee_le',
            ]);
        });
    }
};
