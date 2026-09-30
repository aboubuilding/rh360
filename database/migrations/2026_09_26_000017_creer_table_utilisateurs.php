<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table utilisateurs (reprise de la table « users » de l'application Python EXPERT RH 360 v0.20.2).
 * Les clés étrangères sont ajoutées par la migration finale « ajouter_cles_etrangeres ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('utilisateurs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id');
            $table->string('nom_complet');
            $table->string('email')->nullable();
            $table->string('chemin_photo_profil', 500)->nullable();
            $table->string('identifiant', 200);
            $table->string('password');
            $table->rememberToken();
            $table->string('role', 100)->default('admin');
            $table->boolean('actif')->default(true);
            $table->dateTime('derniere_connexion')->nullable();
            $table->timestamps();
            $table->unique(['entreprise_id', 'identifiant'], 'utilisateurs_entreprise_id_identifiant_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('utilisateurs');
    }
};
