<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table campagnes_evaluation (reprise de la table « performance_campaigns » de l'application Python EXPERT RH 360 v0.20.2).
 * Les clés étrangères sont ajoutées par la migration finale « ajouter_cles_etrangeres ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campagnes_evaluation', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id');
            $table->string('intitule');
            $table->integer('annee');
            $table->date('date_debut');
            $table->date('date_fin');
            $table->string('statut', 60)->default('préparation');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campagnes_evaluation');
    }
};
