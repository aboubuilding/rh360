<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table permissions_utilisateurs (reprise de la table « user_permission_overrides » de l'application Python EXPERT RH 360 v0.20.2).
 * Les clés étrangères sont ajoutées par la migration finale « ajouter_cles_etrangeres ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions_utilisateurs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id');
            $table->foreignId('utilisateur_id');
            $table->string('permission', 240);
            $table->boolean('autorise')->default(false);
            $table->timestamps();
            $table->unique(['utilisateur_id', 'permission'], 'permissions_utilisateurs_utilisateur_id_permission_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions_utilisateurs');
    }
};
