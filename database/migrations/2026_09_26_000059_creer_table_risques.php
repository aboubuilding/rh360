<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table risques (reprise de la table « risk_registers » de l'application Python EXPERT RH 360 v0.20.2).
 * Les clés étrangères sont ajoutées par la migration finale « ajouter_cles_etrangeres ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risques', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id');
            $table->string('cle_soumission', 72);
            $table->string('intitule');
            $table->string('famille', 60);
            $table->string('site');
            $table->foreignId('poste_id')->nullable();
            $table->string('activite', 300);
            $table->text('danger');
            $table->text('consequences');
            $table->date('date_identification');
            $table->foreignId('responsable_salarie_id');
            $table->date('date_echeance_revue')->index();
            $table->string('statut', 50)->default('active');
            $table->string('motif_archivage', 500)->nullable();
            $table->integer('revision_perimetre')->default(1);
            $table->integer('revision_mesures')->default(1);
            $table->foreignId('cree_par');
            $table->foreignId('modifie_par');
            $table->integer('revision')->default(1);
            $table->timestamps();
            $table->unique(['entreprise_id', 'cle_soumission'], 'risques_entreprise_id_cle_soumission_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risques');
    }
};
