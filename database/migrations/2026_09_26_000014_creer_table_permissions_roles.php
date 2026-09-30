<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table permissions_roles (reprise de la table « role_permissions » de l'application Python EXPERT RH 360 v0.20.2).
 * Les clés étrangères sont ajoutées par la migration finale « ajouter_cles_etrangeres ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id');
            $table->string('role', 100);
            $table->string('permission', 240);
            $table->boolean('autorise')->default(false);
            $table->timestamps();
            $table->unique(['entreprise_id', 'role', 'permission'], 'permissions_roles_entreprise_id_role_permission_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions_roles');
    }
};
