<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table formations (reprise de la table « training_courses » de l'application Python EXPERT RH 360 v0.20.2).
 * Les clés étrangères sont ajoutées par la migration finale « ajouter_cles_etrangeres ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('formations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id');
            $table->string('code', 80);
            $table->string('intitule');
            $table->string('domaine', 240)->nullable();
            $table->text('objectif')->nullable();
            $table->decimal('duree_heures', 10, 2)->default(0);
            $table->string('modalite', 60)->default('présentiel');
            $table->boolean('actif')->default(true);
            $table->timestamps();
            $table->unique(['entreprise_id', 'code'], 'formations_entreprise_id_code_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('formations');
    }
};
