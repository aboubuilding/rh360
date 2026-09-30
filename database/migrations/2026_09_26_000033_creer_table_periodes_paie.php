<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table periodes_paie (reprise de la table « payroll_periods » de l'application Python EXPERT RH 360 v0.20.2).
 * Les clés étrangères sont ajoutées par la migration finale « ajouter_cles_etrangeres ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('periodes_paie', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id');
            $table->integer('annee');
            $table->integer('mois');
            $table->string('statut', 60)->default('open');
            $table->dateTime('valide_le')->nullable();
            $table->foreignId('valide_par')->nullable();
            $table->timestamps();
            $table->unique(['entreprise_id', 'annee', 'mois'], 'periodes_paie_entreprise_id_annee_mois_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('periodes_paie');
    }
};
