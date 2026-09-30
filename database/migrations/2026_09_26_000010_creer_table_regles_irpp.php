<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table regles_irpp (reprise de la table « payroll_tax_rules » de l'application Python EXPERT RH 360 v0.20.2).
 * Les clés étrangères sont ajoutées par la migration finale « ajouter_cles_etrangeres ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regles_irpp', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id');
            $table->date('debut_effet');
            $table->date('fin_effet')->nullable();
            $table->string('reference_legale')->nullable();
            $table->decimal('taux_abattement_professionnel', 9, 4)->default(28);
            $table->decimal('plafond_abattement_professionnel', 15, 2)->default(10000000);
            $table->decimal('deduction_mensuelle_par_charge', 15, 2)->default(10000);
            $table->integer('nombre_max_charges')->default(6);
            $table->json('tranches');
            $table->json('taux_tranches');
            $table->boolean('actif')->default(true);
            $table->timestamps();
            $table->unique(['entreprise_id', 'debut_effet'], 'regles_irpp_entreprise_id_debut_effet_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regles_irpp');
    }
};
