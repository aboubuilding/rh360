<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table regles_evolution (reprise de la table « evolution_rules » de l'application Python EXPERT RH 360 v0.20.2).
 * Les clés étrangères sont ajoutées par la migration finale « ajouter_cles_etrangeres ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regles_evolution', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referentiel_id');
            $table->string('code', 240);
            $table->string('type_evolution', 120);
            $table->string('niveau_source', 80);
            $table->string('intitule_source')->nullable();
            $table->string('reference_source')->nullable();
            $table->text('portee')->nullable();
            $table->integer('priorite')->default(100);
            $table->integer('mois_min')->nullable();
            $table->integer('mois_max')->nullable();
            $table->boolean('anticipation_autorisee')->default(false);
            $table->string('condition_anticipation')->nullable();
            $table->string('mode_reinitialisation_anticipation', 80)->default('new_step_effective_date');
            $table->boolean('validation_requise')->default(true);
            $table->text('regle_transitoire')->nullable();
            $table->date('debut_effet')->nullable();
            $table->date('fin_effet')->nullable();
            $table->boolean('actif')->default(true);
            $table->string('statut', 50)->default('active');
            $table->timestamps();
            $table->unique(['referentiel_id', 'code'], 'regles_evolution_referentiel_id_code_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regles_evolution');
    }
};
