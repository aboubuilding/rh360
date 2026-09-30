<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table types_structures (reprise de la table « org_unit_types » de l'application Python EXPERT RH 360 v0.20.2).
 * Les clés étrangères sont ajoutées par la migration finale « ajouter_cles_etrangeres ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('types_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id');
            $table->string('code', 100);
            $table->string('nom', 240);
            $table->integer('ordre')->default(100);
            $table->boolean('actif')->default(true);
            $table->timestamps();
            $table->unique(['entreprise_id', 'code'], 'types_structures_entreprise_id_code_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('types_structures');
    }
};
