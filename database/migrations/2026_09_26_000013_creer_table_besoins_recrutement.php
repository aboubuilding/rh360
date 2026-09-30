<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table besoins_recrutement (reprise de la table « recruitment_requests » de l'application Python EXPERT RH 360 v0.20.2).
 * Les clés étrangères sont ajoutées par la migration finale « ajouter_cles_etrangeres ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('besoins_recrutement', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id');
            $table->string('reference', 100);
            $table->string('intitule_poste');
            $table->string('departement')->nullable();
            $table->integer('nombre_postes')->default(1);
            $table->string('type_contrat', 60)->default('CDI');
            $table->date('date_cible')->nullable();
            $table->text('motif')->nullable();
            $table->string('statut', 60)->default('à valider');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('besoins_recrutement');
    }
};
