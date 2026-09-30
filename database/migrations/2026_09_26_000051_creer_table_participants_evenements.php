<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table participants_evenements (reprise de la table « safety_participants » de l'application Python EXPERT RH 360 v0.20.2).
 * Les clés étrangères sont ajoutées par la migration finale « ajouter_cles_etrangeres ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('participants_evenements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evenement_id');
            $table->foreignId('salarie_id');
            $table->timestamps();
            $table->unique(['evenement_id', 'salarie_id'], 'participants_evenements_evenement_id_salarie_id_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('participants_evenements');
    }
};
