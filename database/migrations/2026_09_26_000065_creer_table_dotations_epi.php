<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table dotations_epi (reprise de la table « ppe_distributions » de l'application Python EXPERT RH 360 v0.20.2).
 * Les clés étrangères sont ajoutées par la migration finale « ajouter_cles_etrangeres ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dotations_epi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id');
            $table->foreignId('salarie_id');
            $table->foreignId('risque_id')->nullable();
            $table->string('cle_soumission', 72);
            $table->string('categorie', 60);
            $table->string('intitule');
            $table->integer('quantite');
            $table->string('unite', 80);
            $table->string('numero_serie', 200)->nullable();
            $table->string('taille', 120)->nullable();
            $table->date('date_remise');
            $table->date('date_expiration')->nullable()->index();
            $table->date('date_verification')->nullable()->index();
            $table->string('emetteur');
            $table->string('reference_recu', 240)->nullable();
            $table->string('statut', 50)->default('issued');
            $table->string('motif', 500)->nullable();
            $table->foreignId('cree_par');
            $table->foreignId('modifie_par');
            $table->integer('revision')->default(1);
            $table->timestamps();
            $table->unique(['entreprise_id', 'cle_soumission'], 'dotations_epi_entreprise_id_cle_soumission_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dotations_epi');
    }
};
