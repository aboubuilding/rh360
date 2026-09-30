<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table alertes_contrats (reprise de la table « contract_alerts » de l'application Python EXPERT RH 360 v0.20.2).
 * Les clés étrangères sont ajoutées par la migration finale « ajouter_cles_etrangeres ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alertes_contrats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id');
            $table->foreignId('contrat_id');
            $table->string('cle', 160);
            $table->string('intitule');
            $table->date('date_echeance');
            $table->string('base_calcul', 128);
            $table->boolean('en_cours')->default(true);
            $table->dateTime('cloture_le')->nullable();
            $table->foreignId('cloture_par')->nullable();
            $table->text('note_cloture')->nullable();
            $table->foreignId('piece_id')->nullable();
            $table->timestamps();
            $table->unique(['contrat_id', 'cle'], 'alertes_contrats_contrat_id_cle_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alertes_contrats');
    }
};
