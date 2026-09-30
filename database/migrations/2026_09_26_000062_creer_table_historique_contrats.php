<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table historique_contrats (reprise de la table « contract_history » de l'application Python EXPERT RH 360 v0.20.2).
 * Les clés étrangères sont ajoutées par la migration finale « ajouter_cles_etrangeres ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historique_contrats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id');
            $table->foreignId('contrat_id');
            $table->string('action', 120);
            $table->json('instantane');
            $table->foreignId('utilisateur_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historique_contrats');
    }
};
