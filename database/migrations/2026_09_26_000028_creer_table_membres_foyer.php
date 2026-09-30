<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table membres_foyer (reprise de la table « household_members » de l'application Python EXPERT RH 360 v0.20.2).
 * Les clés étrangères sont ajoutées par la migration finale « ajouter_cles_etrangeres ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membres_foyer', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salarie_id');
            $table->string('lien_parente', 160);
            $table->string('nom');
            $table->string('prenoms');
            $table->date('date_naissance')->nullable();
            $table->string('lieu_naissance')->nullable();
            $table->boolean('est_enfant_declare')->default(false);
            $table->boolean('est_a_charge')->default(false);
            $table->date('date_debut_charge')->nullable();
            $table->date('date_fin_charge')->nullable();
            $table->string('chemin_photo', 500)->nullable();
            $table->string('chemin_acte_naissance', 500)->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membres_foyer');
    }
};
