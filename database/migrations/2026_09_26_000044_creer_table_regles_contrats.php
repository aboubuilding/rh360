<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table regles_contrats (reprise de la table « contract_rules » de l'application Python EXPERT RH 360 v0.20.2).
 * Les clés étrangères sont ajoutées par la migration finale « ajouter_cles_etrangeres ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regles_contrats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id');
            $table->string('type_contrat', 160);
            $table->foreignId('categorie_id');
            $table->date('date_effet');
            $table->json('parametres');
            $table->foreignId('cree_par');
            $table->timestamps();
            $table->unique(['entreprise_id', 'type_contrat', 'categorie_id', 'date_effet'], 'regles_contrats_aa9df6_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regles_contrats');
    }
};
