<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table referentiels_classification (reprise de la table « classification_frameworks » de l'application Python EXPERT RH 360 v0.20.2).
 * Les clés étrangères sont ajoutées par la migration finale « ajouter_cles_etrangeres ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referentiels_classification', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id');
            $table->string('code', 160);
            $table->string('nom');
            $table->string('type_referentiel', 60)->default('enterprise');
            $table->string('niveau_source', 80)->default('interne');
            $table->string('intitule_source')->nullable();
            $table->string('reference_source')->nullable();
            $table->text('portee')->nullable();
            $table->integer('priorite')->default(100);
            $table->date('debut_effet')->nullable();
            $table->date('fin_effet')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();
            $table->unique(['entreprise_id', 'code'], 'referentiels_classification_entreprise_id_code_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referentiels_classification');
    }
};
