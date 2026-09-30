<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table notifications_contrats (reprise de la table « contract_notifications » de l'application Python EXPERT RH 360 v0.20.2).
 * Les clés étrangères sont ajoutées par la migration finale « ajouter_cles_etrangeres ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications_contrats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id');
            $table->foreignId('alerte_id');
            $table->foreignId('utilisateur_id');
            $table->string('base_calcul', 128);
            $table->integer('seuil');
            $table->dateTime('lu_le')->nullable();
            $table->timestamps();
            $table->unique(['alerte_id', 'base_calcul', 'seuil', 'utilisateur_id'], 'notifications_contrats_196854_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications_contrats');
    }
};
