<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table elements_paie_salaries (reprise de la table « employee_payroll_elements » de l'application Python EXPERT RH 360 v0.20.2).
 * Les clés étrangères sont ajoutées par la migration finale « ajouter_cles_etrangeres ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('elements_paie_salaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id');
            $table->foreignId('salarie_id');
            $table->foreignId('rubrique_id');
            $table->decimal('montant', 15, 2)->default(0);
            $table->boolean('actif')->default(true);
            $table->text('observations')->nullable();
            $table->timestamps();
            $table->unique(['entreprise_id', 'salarie_id', 'rubrique_id'], 'elements_paie_salaries_a8071a_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('elements_paie_salaries');
    }
};
