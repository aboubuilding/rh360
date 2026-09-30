<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table candidats (reprise de la table « recruitment_candidates » de l'application Python EXPERT RH 360 v0.20.2).
 * Les clés étrangères sont ajoutées par la migration finale « ajouter_cles_etrangeres ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id');
            $table->foreignId('besoin_id')->nullable();
            $table->string('nom');
            $table->string('prenoms');
            $table->string('email')->nullable();
            $table->string('telephone', 120)->nullable();
            $table->string('source', 240)->nullable();
            $table->string('etape', 80)->default('candidature reçue');
            $table->decimal('score', 8, 2)->nullable();
            $table->dateTime('date_entretien')->nullable();
            $table->string('decision', 80)->nullable();
            $table->date('date_integration')->nullable();
            $table->text('observations')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidats');
    }
};
