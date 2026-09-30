<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table heures_supplementaires (reprise de la table « payroll_overtime_acts » de l'application Python EXPERT RH 360 v0.20.2).
 * Les clés étrangères sont ajoutées par la migration finale « ajouter_cles_etrangeres ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('heures_supplementaires', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id');
            $table->foreignId('salarie_id');
            $table->string('reference', 160);
            $table->date('debut_travail');
            $table->date('fin_travail');
            $table->foreignId('periode_paiement_id');
            $table->foreignId('periode_origine_id')->nullable();
            $table->decimal('heures_hs20', 10, 2)->default(0);
            $table->decimal('heures_hs40', 10, 2)->default(0);
            $table->decimal('heures_hs65_jour', 10, 2)->default(0);
            $table->decimal('heures_hs65_nuit', 10, 2)->default(0);
            $table->decimal('heures_hs100', 10, 2)->default(0);
            $table->decimal('salaire_base_fige', 15, 2)->default(0);
            $table->decimal('sursalaire_fige', 15, 2)->default(0);
            $table->decimal('taux_horaire', 9, 4)->default(0);
            $table->boolean('est_rappel')->default(false);
            $table->text('motif')->nullable();
            $table->text('motif_retard')->nullable();
            $table->foreignId('cree_par')->nullable();
            $table->timestamps();
            $table->unique(['entreprise_id', 'reference'], 'heures_supplementaires_entreprise_id_reference_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('heures_supplementaires');
    }
};
