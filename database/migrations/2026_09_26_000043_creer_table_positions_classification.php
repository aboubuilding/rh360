<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table positions_classification (reprise de la table « classification_positions » de l'application Python EXPERT RH 360 v0.20.2).
 * Les clés étrangères sont ajoutées par la migration finale « ajouter_cles_etrangeres ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('positions_classification', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referentiel_id');
            $table->string('code', 240);
            $table->foreignId('categorie_id');
            $table->foreignId('classe_id')->nullable();
            $table->foreignId('echelon_id')->nullable();
            $table->integer('montant_salaire')->nullable();
            $table->integer('salaire_minimum')->nullable();
            $table->foreignId('position_conformite_id')->nullable();
            $table->integer('ordre')->default(100);
            $table->date('debut_effet')->nullable();
            $table->date('fin_effet')->nullable();
            $table->foreignId('position_suivante_id')->nullable();
            $table->boolean('actif')->default(true);
            $table->string('statut', 50)->default('active');
            $table->timestamps();
            $table->unique(['referentiel_id', 'code'], 'positions_classification_referentiel_id_code_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('positions_classification');
    }
};
