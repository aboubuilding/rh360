<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table operations_epi (reprise de la table « ppe_operations » de l'application Python EXPERT RH 360 v0.20.2).
 * Les clés étrangères sont ajoutées par la migration finale « ajouter_cles_etrangeres ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operations_epi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id');
            $table->foreignId('dotation_id');
            $table->string('cle_soumission', 72);
            $table->string('nature', 50);
            $table->date('date_evenement');
            $table->integer('quantite')->nullable();
            $table->date('date_prochaine_verification')->nullable();
            $table->string('intervenant');
            $table->text('resultat');
            $table->string('motif_annulation', 500)->nullable();
            $table->foreignId('cree_par');
            $table->timestamps();
            $table->unique(['entreprise_id', 'cle_soumission'], 'operations_epi_entreprise_id_cle_soumission_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operations_epi');
    }
};
