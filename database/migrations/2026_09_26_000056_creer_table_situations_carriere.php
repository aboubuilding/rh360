<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table situations_carriere (reprise de la table « employee_career_states » de l'application Python EXPERT RH 360 v0.20.2).
 * Les clés étrangères sont ajoutées par la migration finale « ajouter_cles_etrangeres ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('situations_carriere', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id');
            $table->foreignId('salarie_id');
            $table->foreignId('position_classification_id')->nullable();
            $table->foreignId('position_ouverture_id')->nullable();
            $table->date('date_reference_ouverture')->nullable();
            $table->date('date_effet_categorie')->nullable();
            $table->date('date_effet_classe')->nullable();
            $table->date('date_effet_echelon')->nullable();
            $table->date('date_reference_avancement')->nullable();
            $table->string('type_source', 80)->default('manual');
            $table->string('categorie_source', 120)->nullable();
            $table->string('reference_source')->nullable();
            $table->string('reference_acte')->nullable();
            $table->date('date_acte')->nullable();
            $table->string('chemin_justificatif', 500)->nullable();
            $table->text('observations')->nullable();
            $table->string('reference_lot_reprise', 240)->nullable();
            $table->string('statut_historique', 80)->default('not_recovered');
            $table->string('statut_fiabilite', 80)->default('to_confirm');
            $table->foreignId('enregistre_par')->nullable();
            $table->dateTime('enregistre_le');
            $table->timestamps();
            $table->unique(['salarie_id'], 'situations_carriere_salarie_id_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('situations_carriere');
    }
};
