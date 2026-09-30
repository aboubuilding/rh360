<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table instantanes_carriere (reprise de la table « career_situation_snapshots » de l'application Python EXPERT RH 360 v0.20.2).
 * Les clés étrangères sont ajoutées par la migration finale « ajouter_cles_etrangeres ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instantanes_carriere', function (Blueprint $table) {
            $table->foreignId('mouvement_id')->primary();
            $table->foreignId('entreprise_id');
            $table->date('date_effet');
            $table->json('details');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instantanes_carriere');
    }
};
