<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table types_conges (reprise de la table « leave_types » de l'application Python EXPERT RH 360 v0.20.2).
 * Les clés étrangères sont ajoutées par la migration finale « ajouter_cles_etrangeres ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('types_conges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id');
            $table->string('code', 80);
            $table->string('nom', 240);
            $table->string('categorie', 120)->default('congé');
            $table->string('unite', 60)->default('jour_calendaire');
            $table->decimal('droit_annuel', 12, 2)->default(0);
            $table->boolean('remunere')->default(true);
            $table->boolean('justificatif_requis')->default(false);
            $table->string('reference_legale', 240)->nullable();
            $table->decimal('duree_max', 10, 2)->nullable();
            $table->string('portee_duree_max', 60)->nullable();
            $table->string('traitement_salarial', 60)->default('maintien');
            $table->string('impact_conge_annuel', 60)->default('aucune');
            $table->string('impact_anciennete', 60)->default('maintenue');
            $table->integer('delai_justification_jours')->nullable();
            $table->boolean('autorisation_prealable_requise')->default(false);
            $table->boolean('actif')->default(true);
            $table->timestamps();
            $table->unique(['entreprise_id', 'code'], 'types_conges_entreprise_id_code_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('types_conges');
    }
};
