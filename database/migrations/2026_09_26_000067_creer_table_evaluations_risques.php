<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table evaluations_risques (reprise de la table « risk_assessments » de l'application Python EXPERT RH 360 v0.20.2).
 * Les clés étrangères sont ajoutées par la migration finale « ajouter_cles_etrangeres ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluations_risques', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id');
            $table->foreignId('risque_id');
            $table->string('cle_soumission', 72);
            $table->date('date_evaluation');
            $table->string('motif', 60);
            $table->integer('gravite');
            $table->integer('probabilite');
            $table->integer('score');
            $table->string('niveau', 50);
            $table->string('version_methode', 80);
            $table->text('mesures_existantes');
            $table->text('justification');
            $table->date('date_prochaine_revue');
            $table->integer('revision_perimetre');
            $table->integer('revision_mesures');
            $table->json('instantane_contexte');
            $table->foreignId('cree_par');
            $table->timestamps();
            $table->unique(['entreprise_id', 'cle_soumission'], 'evaluations_risques_entreprise_id_cle_soumission_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluations_risques');
    }
};
