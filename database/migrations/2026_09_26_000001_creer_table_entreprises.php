<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table entreprises (reprise de la table « enterprises » de l'application Python EXPERT RH 360 v0.20.2).
 * Les clés étrangères sont ajoutées par la migration finale « ajouter_cles_etrangeres ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entreprises', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('sigle', 100)->nullable();
            $table->string('forme_juridique', 200)->nullable();
            $table->string('nif', 200)->nullable();
            $table->string('numero_employeur_cnss', 200)->nullable();
            $table->string('secteur')->nullable();
            $table->text('adresse')->nullable();
            $table->string('ville', 200)->nullable();
            $table->string('pays', 200)->default('Togo');
            $table->string('telephone', 100)->nullable();
            $table->string('email')->nullable();
            $table->string('devise', 50)->default('XOF');
            $table->date('date_bascule')->nullable();
            $table->string('chemin_logo', 500)->nullable();
            $table->string('direction_emettrice')->nullable();
            $table->string('service_emetteur')->nullable();
            $table->text('texte_en_tete')->nullable();
            $table->text('texte_pied_page')->nullable();
            $table->string('nom_signataire')->nullable();
            $table->string('fonction_signataire')->nullable();
            $table->string('lieu_signature', 240)->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entreprises');
    }
};
