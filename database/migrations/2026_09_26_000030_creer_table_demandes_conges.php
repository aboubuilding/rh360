<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table demandes_conges (reprise de la table « leave_requests » de l'application Python EXPERT RH 360 v0.20.2).
 * Les clés étrangères sont ajoutées par la migration finale « ajouter_cles_etrangeres ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demandes_conges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id');
            $table->string('numero_demande', 160);
            $table->foreignId('salarie_id');
            $table->foreignId('type_conge_id');
            $table->date('date_demande');
            $table->date('date_debut');
            $table->date('date_reprise');
            $table->decimal('duree_jours', 10, 2);
            $table->text('motif')->nullable();
            $table->string('remplacant')->nullable();
            $table->string('statut', 80)->default('draft');
            $table->date('date_decision')->nullable();
            $table->string('reference_acte')->nullable();
            $table->date('date_acte')->nullable();
            $table->foreignId('cree_par')->nullable();
            $table->foreignId('valide_par')->nullable();
            $table->timestamps();
            $table->unique(['entreprise_id', 'numero_demande'], 'demandes_conges_entreprise_id_numero_demande_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demandes_conges');
    }
};
