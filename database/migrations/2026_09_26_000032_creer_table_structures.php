<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table structures (reprise de la table « org_units » de l'application Python EXPERT RH 360 v0.20.2).
 * Les clés étrangères sont ajoutées par la migration finale « ajouter_cles_etrangeres ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id');
            $table->foreignId('type_structure_id');
            $table->foreignId('parent_id')->nullable();
            $table->string('code', 100);
            $table->string('nom');
            $table->string('localisation')->nullable();
            $table->string('centre_cout', 200)->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();
            $table->unique(['entreprise_id', 'code'], 'structures_entreprise_id_code_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('structures');
    }
};
