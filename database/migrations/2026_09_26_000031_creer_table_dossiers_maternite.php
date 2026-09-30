<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table dossiers_maternite (reprise de la table « maternity_records » de l'application Python EXPERT RH 360 v0.20.2).
 * Les clés étrangères sont ajoutées par la migration finale « ajouter_cles_etrangeres ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dossiers_maternite', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id');
            $table->foreignId('salarie_id');
            $table->date('date_declaration')->nullable();
            $table->string('chemin_certificat_medical', 500)->nullable();
            $table->boolean('risque_poste_identifie')->default(false);
            $table->text('amenagement_temporaire')->nullable();
            $table->string('statut', 80)->default('déclarée');
            $table->date('date_consultation_1')->nullable();
            $table->date('date_consultation_2')->nullable();
            $table->date('date_consultation_3')->nullable();
            $table->date('date_prevue_accouchement')->nullable();
            $table->date('date_debut_conge')->nullable();
            $table->date('date_reprise_effective')->nullable();
            $table->string('reference_acte')->nullable();
            $table->date('date_acte')->nullable();
            $table->text('observations')->nullable();
            $table->foreignId('cree_par')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dossiers_maternite');
    }
};
