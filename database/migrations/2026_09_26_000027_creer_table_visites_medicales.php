<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table visites_medicales (reprise de la table « health_visits » de l'application Python EXPERT RH 360 v0.20.2).
 * Les clés étrangères sont ajoutées par la migration finale « ajouter_cles_etrangeres ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visites_medicales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id');
            $table->foreignId('salarie_id');
            $table->string('type_visite', 60);
            $table->date('date_prevue')->index();
            $table->date('date_realisation')->nullable();
            $table->string('statut', 50)->default('planned');
            $table->string('aptitude', 60)->default('pending');
            $table->string('prestataire')->nullable();
            $table->string('reference_avis', 200)->nullable();
            $table->text('restrictions')->nullable();
            $table->date('date_prochaine_echeance')->nullable()->index();
            $table->string('motif_annulation')->nullable();
            $table->foreignId('visite_origine_id')->nullable();
            $table->foreignId('cree_par');
            $table->foreignId('modifie_par');
            $table->integer('revision')->default(1);
            $table->timestamps();
            $table->unique(['entreprise_id', 'salarie_id', 'type_visite', 'date_prevue'], 'visites_medicales_57b989_unique');
            $table->unique(['visite_origine_id'], 'visites_medicales_visite_origine_id_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visites_medicales');
    }
};
