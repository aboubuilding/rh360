<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Règles d'ancienneté versionnées par date d'effet : une entreprise peut avoir plusieurs
 * règles successives (une par date de début d'effet), au lieu d'une seule.
 * Aucune donnée n'est modifiée ; l'index composite est créé avant la suppression de
 * l'ancien index, qui sert aussi la clé étrangère vers entreprises.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('regles_anciennete', function (Blueprint $table) {
            $table->unique(['entreprise_id', 'debut_effet'], 'regles_anciennete_entreprise_debut_unique');
        });

        Schema::table('regles_anciennete', function (Blueprint $table) {
            $table->dropUnique('regles_anciennete_entreprise_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('regles_anciennete', function (Blueprint $table) {
            $table->unique(['entreprise_id'], 'regles_anciennete_entreprise_id_unique');
        });

        Schema::table('regles_anciennete', function (Blueprint $table) {
            $table->dropUnique('regles_anciennete_entreprise_debut_unique');
        });
    }
};
