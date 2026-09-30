<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table plan_formation (reprise de la table « training_plan_items » de l'application Python EXPERT RH 360 v0.20.2).
 * Les clés étrangères sont ajoutées par la migration finale « ajouter_cles_etrangeres ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_formation', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id');
            $table->string('intitule');
            $table->integer('annee');
            $table->date('debut_prevu')->nullable();
            $table->decimal('montant_budget', 15, 2)->default(0);
            $table->string('statut', 60)->default('prévu');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_formation');
    }
};
