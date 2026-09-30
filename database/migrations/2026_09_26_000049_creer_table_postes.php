<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table postes (reprise de la table « positions » de l'application Python EXPERT RH 360 v0.20.2).
 * Les clés étrangères sont ajoutées par la migration finale « ajouter_cles_etrangeres ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('postes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id');
            $table->foreignId('structure_id');
            $table->string('code', 100);
            $table->string('intitule');
            $table->string('categorie', 200)->nullable();
            $table->integer('effectif_cible')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();
            $table->unique(['entreprise_id', 'code'], 'postes_entreprise_id_code_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('postes');
    }
};
