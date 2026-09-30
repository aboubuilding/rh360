<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table rubriques_paie (reprise de la table « payroll_rubrics » de l'application Python EXPERT RH 360 v0.20.2).
 * Les clés étrangères sont ajoutées par la migration finale « ajouter_cles_etrangeres ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rubriques_paie', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id');
            $table->string('code', 80);
            $table->string('nom');
            $table->text('description')->nullable();
            $table->string('nature', 50)->default('gain');
            $table->string('recurrence', 50)->default('fixed');
            $table->string('mode_calcul', 50)->default('amount');
            $table->decimal('taux', 9, 4)->default(0);
            $table->decimal('montant_defaut', 15, 2)->default(0);
            $table->boolean('imposable')->default(true);
            $table->string('traitement_fiscal', 60)->default('taxable');
            $table->decimal('pourcentage_imposable', 9, 4)->default(100);
            $table->string('methode_evaluation', 60)->default('amount');
            $table->boolean('justificatif_requis')->default(false);
            $table->string('reference_fiscale')->nullable();
            $table->boolean('soumis_cotisation')->default(true);
            $table->boolean('actif')->default(true);
            $table->timestamps();
            $table->unique(['entreprise_id', 'code'], 'rubriques_paie_entreprise_id_code_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rubriques_paie');
    }
};
