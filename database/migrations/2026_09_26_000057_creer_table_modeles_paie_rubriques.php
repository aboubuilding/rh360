<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table modeles_paie_rubriques (reprise de la table « payroll_template_rubrics » de l'application Python EXPERT RH 360 v0.20.2).
 * Les clés étrangères sont ajoutées par la migration finale « ajouter_cles_etrangeres ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modeles_paie_rubriques', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id');
            $table->foreignId('modele_id');
            $table->foreignId('rubrique_id');
            $table->decimal('montant_defaut', 15, 2)->default(0);
            $table->boolean('obligatoire')->default(false);
            $table->integer('ordre')->default(100);
            $table->boolean('actif')->default(true);
            $table->timestamps();
            $table->unique(['modele_id', 'rubrique_id'], 'modeles_paie_rubriques_modele_id_rubrique_id_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modeles_paie_rubriques');
    }
};
