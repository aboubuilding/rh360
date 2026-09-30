<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table entretiens_evaluation (reprise de la table « performance_reviews » de l'application Python EXPERT RH 360 v0.20.2).
 * Les clés étrangères sont ajoutées par la migration finale « ajouter_cles_etrangeres ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entretiens_evaluation', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id');
            $table->foreignId('campagne_id');
            $table->foreignId('salarie_id');
            $table->decimal('note_auto_evaluation', 8, 2)->nullable();
            $table->decimal('note_manager', 8, 2)->nullable();
            $table->decimal('note_finale', 8, 2)->nullable();
            $table->text('points_forts')->nullable();
            $table->text('besoins_developpement')->nullable();
            $table->text('commentaire_manager')->nullable();
            $table->string('statut', 60)->default('à préparer');
            $table->date('date_entretien')->nullable();
            $table->text('action_amelioration')->nullable();
            $table->date('date_echeance_amelioration')->nullable();
            $table->timestamps();
            $table->unique(['campagne_id', 'salarie_id'], 'entretiens_evaluation_campagne_id_salarie_id_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entretiens_evaluation');
    }
};
