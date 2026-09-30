<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table pieces_jointes_sst (reprise de la table « sst_attachments » de l'application Python EXPERT RH 360 v0.20.2).
 * Les clés étrangères sont ajoutées par la migration finale « ajouter_cles_etrangeres ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pieces_jointes_sst', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id');
            $table->string('nature', 50);
            $table->integer('fiche_id')->index();
            $table->string('cle_soumission', 72);
            $table->string('libelle');
            $table->string('chemin', 300);
            $table->string('type_mime', 160);
            $table->foreignId('auteur_id');
            $table->timestamps();
            $table->unique(['entreprise_id', 'cle_soumission'], 'pieces_jointes_sst_entreprise_id_cle_soumission_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pieces_jointes_sst');
    }
};
