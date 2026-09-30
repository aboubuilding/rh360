<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table documents_salaries (reprise de la table « employee_documents » de l'application Python EXPERT RH 360 v0.20.2).
 * Les clés étrangères sont ajoutées par la migration finale « ajouter_cles_etrangeres ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents_salaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salarie_id');
            $table->string('type_document', 240);
            $table->string('chemin_fichier', 500);
            $table->date('date_document')->nullable();
            $table->date('date_expiration')->nullable();
            $table->text('observations')->nullable();
            $table->boolean('actif')->default(true);
            $table->integer('remplace_document_id')->nullable();
            $table->dateTime('archive_le')->nullable();
            $table->text('motif_archivage')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents_salaries');
    }
};
