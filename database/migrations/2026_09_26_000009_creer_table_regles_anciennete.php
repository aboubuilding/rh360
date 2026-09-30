<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table regles_anciennete (reprise de la table « payroll_seniority_rules » de l'application Python EXPERT RH 360 v0.20.2).
 * Les clés étrangères sont ajoutées par la migration finale « ajouter_cles_etrangeres ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regles_anciennete', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id');
            $table->integer('annees_min')->default(0);
            $table->decimal('taux_initial', 9, 4)->default(0);
            $table->decimal('increment_annuel', 12, 2)->default(0);
            $table->decimal('taux_max', 9, 4)->default(0);
            $table->string('mode_base', 60)->default('salary_base');
            $table->date('debut_effet');
            $table->date('fin_effet')->nullable();
            $table->string('reference_legale')->nullable();
            $table->boolean('actif')->default(false);
            $table->timestamps();
            $table->unique(['entreprise_id'], 'regles_anciennete_entreprise_id_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regles_anciennete');
    }
};
