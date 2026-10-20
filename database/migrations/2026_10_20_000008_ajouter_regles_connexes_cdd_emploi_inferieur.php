<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Règles connexes (RG-REM-01 à 05, RG-INF-01 à 03) — colonnes additives et maintien de rémunération.
 */
return new class extends Migration
{
    public function up(): void
    {
        // CDD de remplacement (Code du travail, art. 48, 49, 54, 55 ; CCIT art. 16)
        Schema::table('contrats', function (Blueprint $table) {
            $table->string('motif_recours', 40)->nullable()->after('type_contrat');
            $table->foreignId('titulaire_remplace_id')->nullable()->after('motif_recours')
                ->constrained('salaries')->restrictOnDelete();
            $table->string('terme_evenement', 40)->nullable()->after('date_fin');
            $table->boolean('journalier_paye_fin_journee')->default(false)->after('terme_evenement');
            $table->text('mention_caractere_provisoire')->nullable()->after('journalier_paye_fin_journee');
            $table->string('motif_fin', 40)->nullable()->after('mention_caractere_provisoire');
            $table->date('refus_cdi_equivalent_le')->nullable()->after('motif_fin');
        });

        // Affectation définitive à un poste inférieur : consultation des délégués du personnel (RG-INF-02)
        Schema::table('mouvements_carriere', function (Blueprint $table) {
            $table->date('date_consultation_delegues')->nullable()->after('chemin_justificatif');
            $table->string('chemin_pv_consultation', 500)->nullable()->after('date_consultation_delegues');
        });

        // RG-INF-01 à 03 — salaire et classement maintenus pendant l'affectation ou la mutation
        Schema::create('maintiens_remuneration', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id')->constrained('entreprises')->restrictOnDelete();
            $table->foreignId('salarie_id')->constrained('salaries')->restrictOnDelete();
            $table->foreignId('mouvement_carriere_id')->constrained('mouvements_carriere')->restrictOnDelete();
            $table->string('motif', 40);
            $table->unsignedInteger('salaire_base_maintenu')->nullable()->comment('FCFA');
            $table->foreignId('position_maintenue_id')->nullable()->constrained('positions_classification')->restrictOnDelete();
            $table->date('date_debut');
            $table->date('date_fin')->nullable();
            $table->tinyInteger('etat')->default(1)->index()->comment('1=actif, 0=inactif, -1=supprime');
            $table->timestamps();

            $table->index(['salarie_id', 'date_debut', 'date_fin']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintiens_remuneration');

        Schema::table('mouvements_carriere', function (Blueprint $table) {
            $table->dropColumn(['date_consultation_delegues', 'chemin_pv_consultation']);
        });

        Schema::table('contrats', function (Blueprint $table) {
            $table->dropConstrainedForeignId('titulaire_remplace_id');
            $table->dropColumn(['motif_recours', 'terme_evenement', 'journalier_paye_fin_journee',
                'mention_caractere_provisoire', 'motif_fin', 'refus_cdi_equivalent_le']);
        });
    }
};
