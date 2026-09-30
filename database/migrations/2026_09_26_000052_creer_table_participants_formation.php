<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table participants_formation (reprise de la table « training_participants » de l'application Python EXPERT RH 360 v0.20.2).
 * Les clés étrangères sont ajoutées par la migration finale « ajouter_cles_etrangeres ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('participants_formation', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id');
            $table->foreignId('session_formation_id');
            $table->foreignId('salarie_id');
            $table->string('statut_presence', 60)->default('inscrit');
            $table->decimal('score_avant', 8, 2)->nullable();
            $table->decimal('score_apres', 8, 2)->nullable();
            $table->decimal('note_satisfaction', 8, 2)->nullable();
            $table->text('commentaire_evaluation')->nullable();
            $table->string('reference_attestation', 240)->nullable();
            $table->timestamps();
            $table->unique(['session_formation_id', 'salarie_id'], 'participants_formation_4b2ba2_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('participants_formation');
    }
};
