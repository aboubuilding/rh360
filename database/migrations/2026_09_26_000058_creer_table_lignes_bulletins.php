<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table lignes_bulletins (reprise de la table « payslip_lines » de l'application Python EXPERT RH 360 v0.20.2).
 * Les clés étrangères sont ajoutées par la migration finale « ajouter_cles_etrangeres ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lignes_bulletins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id');
            $table->foreignId('bulletin_id');
            $table->foreignId('rubrique_id')->nullable();
            $table->string('code', 80);
            $table->string('libelle');
            $table->string('nature', 50);
            $table->decimal('montant', 15, 2)->default(0);
            $table->string('traitement_fiscal', 60)->default('taxable');
            $table->decimal('montant_imposable', 15, 2)->default(0);
            $table->integer('ordre')->default(100);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lignes_bulletins');
    }
};
