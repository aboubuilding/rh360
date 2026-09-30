<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table sessions_formation (reprise de la table « training_sessions » de l'application Python EXPERT RH 360 v0.20.2).
 * Les clés étrangères sont ajoutées par la migration finale « ajouter_cles_etrangeres ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sessions_formation', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id');
            $table->foreignId('plan_formation_id')->nullable();
            $table->string('intitule');
            $table->string('prestataire')->nullable();
            $table->string('localisation')->nullable();
            $table->date('date_debut');
            $table->date('date_fin');
            $table->decimal('duree_heures', 10, 2)->default(0);
            $table->decimal('cout_reel', 15, 2)->default(0);
            $table->string('statut', 60)->default('programmée');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions_formation');
    }
};
