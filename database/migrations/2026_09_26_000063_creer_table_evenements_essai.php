<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table evenements_essai (reprise de la table « contract_trial_events » de l'application Python EXPERT RH 360 v0.20.2).
 * Les clés étrangères sont ajoutées par la migration finale « ajouter_cles_etrangeres ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evenements_essai', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id');
            $table->foreignId('contrat_id');
            $table->string('cle_soumission', 72);
            $table->string('nature', 50);
            $table->string('statut', 50)->default('pending');
            $table->json('details');
            $table->foreignId('cree_par');
            $table->foreignId('decide_par')->nullable();
            $table->text('note_decision')->nullable();
            $table->dateTime('decide_le')->nullable();
            $table->timestamps();
            $table->unique(['entreprise_id', 'cle_soumission'], 'evenements_essai_entreprise_id_cle_soumission_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evenements_essai');
    }
};
