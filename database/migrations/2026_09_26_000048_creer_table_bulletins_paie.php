<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table bulletins_paie (reprise de la table « payslips » de l'application Python EXPERT RH 360 v0.20.2).
 * Les clés étrangères sont ajoutées par la migration finale « ajouter_cles_etrangeres ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bulletins_paie', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id');
            $table->foreignId('periode_id');
            $table->foreignId('salarie_id');
            $table->decimal('montant_brut', 15, 2)->default(0);
            $table->decimal('montant_retenues', 15, 2)->default(0);
            $table->decimal('montant_net', 15, 2)->default(0);
            $table->decimal('brut_imposable', 15, 2)->default(0);
            $table->decimal('retenues_sociales_deductibles', 15, 2)->default(0);
            $table->decimal('abattement_professionnel', 15, 2)->default(0);
            $table->decimal('deduction_charges_famille', 15, 2)->default(0);
            $table->decimal('base_imposable', 15, 2)->default(0);
            $table->decimal('montant_irpp', 15, 2)->default(0);
            $table->dateTime('calcule_le');
            $table->timestamps();
            $table->unique(['periode_id', 'salarie_id'], 'bulletins_paie_periode_id_salarie_id_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bulletins_paie');
    }
};
