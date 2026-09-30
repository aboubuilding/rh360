<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table besoins_formation (reprise de la table « training_needs » de l'application Python EXPERT RH 360 v0.20.2).
 * Les clés étrangères sont ajoutées par la migration finale « ajouter_cles_etrangeres ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('besoins_formation', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id');
            $table->foreignId('salarie_id')->nullable();
            $table->string('intitule');
            $table->text('motif')->nullable();
            $table->string('priorite', 50)->default('normale');
            $table->integer('annee_cible');
            $table->string('statut', 60)->default('à étudier');
            $table->foreignId('cree_par')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('besoins_formation');
    }
};
