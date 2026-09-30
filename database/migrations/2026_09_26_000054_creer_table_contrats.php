<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table contrats (reprise de la table « contract_records » de l'application Python EXPERT RH 360 v0.20.2).
 * Les clés étrangères sont ajoutées par la migration finale « ajouter_cles_etrangeres ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contrats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id');
            $table->foreignId('salarie_id');
            $table->foreignId('parent_id')->nullable();
            $table->string('cle_soumission', 72);
            $table->string('statut', 50)->default('draft');
            $table->string('reference', 240);
            $table->string('type_contrat', 160);
            $table->date('date_debut')->index();
            $table->date('date_fin')->nullable();
            $table->foreignId('poste_id');
            $table->foreignId('position_classification_id');
            $table->json('conditions');
            $table->json('regle_figee')->nullable();
            $table->date('date_signature')->nullable();
            $table->string('reference_signee', 240)->nullable();
            $table->integer('revision')->default(1);
            $table->foreignId('cree_par');
            $table->timestamps();
            $table->unique(['entreprise_id', 'cle_soumission'], 'contrats_entreprise_id_cle_soumission_unique');
            $table->unique(['entreprise_id', 'reference'], 'contrats_entreprise_id_reference_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contrats');
    }
};
