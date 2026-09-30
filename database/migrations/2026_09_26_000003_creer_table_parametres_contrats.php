<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table parametres_contrats (reprise de la table « contract_settings » de l'application Python EXPERT RH 360 v0.20.2).
 * Les clés étrangères sont ajoutées par la migration finale « ajouter_cles_etrangeres ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parametres_contrats', function (Blueprint $table) {
            $table->foreignId('entreprise_id')->primary();
            $table->string('seuils')->default('30,15,7,0');
            $table->string('roles')->default('rh,drh');
            $table->integer('revision')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parametres_contrats');
    }
};
