<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table evenements_securite (reprise de la table « safety_events » de l'application Python EXPERT RH 360 v0.20.2).
 * Les clés étrangères sont ajoutées par la migration finale « ajouter_cles_etrangeres ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evenements_securite', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id');
            $table->string('cle_soumission', 72);
            $table->string('type_evenement', 60);
            $table->date('date_survenance')->index();
            $table->string('heure_survenance', 50)->nullable();
            $table->date('date_declaration');
            $table->string('intitule');
            $table->string('localisation');
            $table->text('description');
            $table->text('mesures_immediates')->nullable();
            $table->text('analyse')->nullable();
            $table->string('priorite', 50)->default('normal');
            $table->string('statut', 50)->default('open');
            $table->string('statut_externe', 50)->default('none');
            $table->string('destinataire_externe')->nullable();
            $table->date('date_echeance_externe')->nullable();
            $table->date('date_envoi_externe')->nullable();
            $table->string('reference_externe', 240)->nullable();
            $table->date('date_cloture')->nullable();
            $table->text('synthese_cloture')->nullable();
            $table->string('motif_annulation', 300)->nullable();
            $table->foreignId('cree_par');
            $table->foreignId('modifie_par');
            $table->integer('revision')->default(1);
            $table->timestamps();
            $table->unique(['entreprise_id', 'cle_soumission'], 'evenements_securite_entreprise_id_cle_soumission_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evenements_securite');
    }
};
