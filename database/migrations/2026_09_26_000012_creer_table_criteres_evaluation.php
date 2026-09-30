<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table criteres_evaluation (reprise de la table « performance_criteria » de l'application Python EXPERT RH 360 v0.20.2).
 * Les clés étrangères sont ajoutées par la migration finale « ajouter_cles_etrangeres ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('criteres_evaluation', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id');
            $table->string('code', 80);
            $table->string('libelle');
            $table->string('famille', 80)->default('compétence');
            $table->decimal('ponderation', 8, 2)->default(1);
            $table->boolean('actif')->default(true);
            $table->timestamps();
            $table->unique(['entreprise_id', 'code'], 'criteres_evaluation_entreprise_id_code_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('criteres_evaluation');
    }
};
