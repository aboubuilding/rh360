<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table objectifs_evaluation (reprise de la table « performance_objectives » de l'application Python EXPERT RH 360 v0.20.2).
 * Les clés étrangères sont ajoutées par la migration finale « ajouter_cles_etrangeres ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('objectifs_evaluation', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id');
            $table->foreignId('campagne_id');
            $table->foreignId('salarie_id');
            $table->string('intitule');
            $table->string('indicateur')->nullable();
            $table->string('cible')->nullable();
            $table->decimal('ponderation', 8, 2)->default(1);
            $table->date('date_echeance')->nullable();
            $table->string('statut', 60)->default('à réaliser');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('objectifs_evaluation');
    }
};
