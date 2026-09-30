<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table regles_cotisations (reprise de la table « payroll_contribution_rules » de l'application Python EXPERT RH 360 v0.20.2).
 * Les clés étrangères sont ajoutées par la migration finale « ajouter_cles_etrangeres ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regles_cotisations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id');
            $table->string('code', 80);
            $table->string('nom');
            $table->decimal('taux_salarial', 9, 4)->default(0);
            $table->decimal('taux_patronal', 9, 4)->default(0);
            $table->date('debut_effet');
            $table->date('fin_effet')->nullable();
            $table->string('reference_legale')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();
            $table->unique(['entreprise_id', 'code'], 'regles_cotisations_entreprise_id_code_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regles_cotisations');
    }
};
