<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table historique_sst (reprise de la table « sst_record_history » de l'application Python EXPERT RH 360 v0.20.2).
 * Les clés étrangères sont ajoutées par la migration finale « ajouter_cles_etrangeres ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historique_sst', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id');
            $table->string('nature', 50);
            $table->integer('fiche_id')->index();
            $table->integer('revision');
            $table->json('instantane');
            $table->foreignId('auteur_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historique_sst');
    }
};
