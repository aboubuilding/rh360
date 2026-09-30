<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table categories_classification (reprise de la table « classification_categories » de l'application Python EXPERT RH 360 v0.20.2).
 * Les clés étrangères sont ajoutées par la migration finale « ajouter_cles_etrangeres ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories_classification', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referentiel_id');
            $table->string('code', 160);
            $table->string('libelle');
            $table->integer('ordre')->default(100);
            $table->boolean('actif')->default(true);
            $table->timestamps();
            $table->unique(['referentiel_id', 'code'], 'categories_classification_referentiel_id_code_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories_classification');
    }
};
