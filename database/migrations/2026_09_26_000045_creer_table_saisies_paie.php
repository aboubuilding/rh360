<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table saisies_paie (reprise de la table « payroll_entries » de l'application Python EXPERT RH 360 v0.20.2).
 * Les clés étrangères sont ajoutées par la migration finale « ajouter_cles_etrangeres ».
 */
return new class extends Migration
{
    public function up(): void
    {
        // Doublon : la table est déjà créée par 2026_09_26_000041_creer_table_saisies_paie.
        if (Schema::hasTable('saisies_paie')) {
            return;
        }

        Schema::create('saisies_paie', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id');
            $table->foreignId('periode_id');
            $table->foreignId('salarie_id');
            $table->foreignId('rubrique_id');
            $table->decimal('montant', 15, 2)->default(0);
            $table->string('source', 60)->default('manual');
            $table->timestamps();
            $table->unique(['periode_id', 'salarie_id', 'rubrique_id'], 'saisies_paie_periode_id_salarie_id_rubrique_id_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saisies_paie');
    }
};
