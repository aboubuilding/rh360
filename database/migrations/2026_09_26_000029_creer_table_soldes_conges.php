<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table soldes_conges (reprise de la table « leave_balances » de l'application Python EXPERT RH 360 v0.20.2).
 * Les clés étrangères sont ajoutées par la migration finale « ajouter_cles_etrangeres ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('soldes_conges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id');
            $table->foreignId('salarie_id');
            $table->foreignId('type_conge_id');
            $table->integer('annee');
            $table->decimal('solde_ouverture', 12, 2)->default(0);
            $table->decimal('acquis', 12, 2)->default(0);
            $table->decimal('ajustement', 12, 2)->default(0);
            $table->decimal('consomme', 12, 2)->default(0);
            $table->decimal('reserve', 12, 2)->default(0);
            $table->text('observations')->nullable();
            $table->timestamps();
            $table->unique(['entreprise_id', 'salarie_id', 'type_conge_id', 'annee'], 'soldes_conges_811e31_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('soldes_conges');
    }
};
