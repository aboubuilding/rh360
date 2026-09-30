<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table actions_risques (reprise de la table « risk_actions » de l'application Python EXPERT RH 360 v0.20.2).
 * Les clés étrangères sont ajoutées par la migration finale « ajouter_cles_etrangeres ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('actions_risques', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id');
            $table->foreignId('risque_id');
            $table->string('cle_soumission', 72);
            $table->string('intitule');
            $table->string('type_mesure', 60);
            $table->foreignId('responsable_salarie_id');
            $table->date('date_echeance')->index();
            $table->string('statut', 50)->default('todo');
            $table->date('date_realisation')->nullable();
            $table->text('resultat')->nullable();
            $table->string('motif_annulation', 300)->nullable();
            $table->foreignId('cree_par');
            $table->foreignId('modifie_par');
            $table->integer('revision')->default(1);
            $table->timestamps();
            $table->unique(['entreprise_id', 'cle_soumission'], 'actions_risques_entreprise_id_cle_soumission_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actions_risques');
    }
};
