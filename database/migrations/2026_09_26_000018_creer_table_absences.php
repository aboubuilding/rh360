<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table absences (reprise de la table « absence_records » de l'application Python EXPERT RH 360 v0.20.2).
 * Les clés étrangères sont ajoutées par la migration finale « ajouter_cles_etrangeres ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id');
            $table->foreignId('salarie_id');
            $table->foreignId('type_conge_id');
            $table->dateTime('debut_le');
            $table->dateTime('fin_le')->nullable();
            $table->decimal('duree_heures', 10, 2)->nullable();
            $table->text('motif')->nullable();
            $table->text('justification')->nullable();
            $table->date('date_limite_justification')->nullable();
            $table->string('statut', 80)->default('constatée');
            $table->text('decision_regularisation')->nullable();
            $table->string('qualification', 100)->nullable();
            $table->string('statut_transmission_paie', 60)->default('à préparer');
            $table->string('periode_paie', 50)->nullable();
            $table->dateTime('transmis_paie_le')->nullable();
            $table->foreignId('transmis_paie_par')->nullable();
            $table->foreignId('cree_par')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absences');
    }
};
