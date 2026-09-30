<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table habilitations (reprise de la table « work_authorizations » de l'application Python EXPERT RH 360 v0.20.2).
 * Les clés étrangères sont ajoutées par la migration finale « ajouter_cles_etrangeres ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('habilitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id');
            $table->foreignId('salarie_id');
            $table->foreignId('risque_id')->nullable();
            $table->foreignId('habilitation_origine_id')->nullable();
            $table->string('cle_soumission', 72);
            $table->string('categorie', 60);
            $table->string('intitule');
            $table->text('portee');
            $table->string('emetteur')->nullable();
            $table->string('reference_decision', 240)->nullable();
            $table->string('reference_formation')->nullable();
            $table->date('date_decision')->nullable();
            $table->date('date_debut');
            $table->date('date_fin')->nullable()->index();
            $table->date('date_revue')->index();
            $table->string('statut', 50)->default('draft');
            $table->string('motif', 500)->nullable();
            $table->foreignId('cree_par');
            $table->foreignId('modifie_par');
            $table->integer('revision')->default(1);
            $table->timestamps();
            $table->unique(['habilitation_origine_id'], 'habilitations_habilitation_origine_id_unique');
            $table->unique(['entreprise_id', 'cle_soumission'], 'habilitations_entreprise_id_cle_soumission_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('habilitations');
    }
};
