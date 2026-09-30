<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table pieces_contrats (reprise de la table « contract_evidence » de l'application Python EXPERT RH 360 v0.20.2).
 * Les clés étrangères sont ajoutées par la migration finale « ajouter_cles_etrangeres ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pieces_contrats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id');
            $table->foreignId('contrat_id');
            $table->string('objet', 60);
            $table->string('libelle');
            $table->string('chemin', 500);
            $table->string('type_mime', 160);
            $table->string('empreinte_sha256', 128);
            $table->foreignId('cree_par');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pieces_contrats');
    }
};
