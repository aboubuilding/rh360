<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table modeles_paie (reprise de la table « payroll_templates » de l'application Python EXPERT RH 360 v0.20.2).
 * Les clés étrangères sont ajoutées par la migration finale « ajouter_cles_etrangeres ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modeles_paie', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id');
            $table->foreignId('categorie_id');
            $table->string('nom');
            $table->boolean('actif')->default(true);
            $table->timestamps();
            $table->unique(['entreprise_id', 'categorie_id'], 'modeles_paie_entreprise_id_categorie_id_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modeles_paie');
    }
};
