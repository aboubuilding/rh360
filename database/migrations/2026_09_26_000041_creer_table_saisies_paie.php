<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table saisies_paie — éléments variables saisis pour une période donnée.
 * Une saisie lie : une période, un salarié, une rubrique, une valeur.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saisies_paie', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id');
            $table->foreignId('periode_id');
            $table->foreignId('salarie_id');
            $table->foreignId('rubrique_id');
            $table->decimal('quantite', 10, 2)->default(1);
            $table->decimal('taux', 9, 4)->default(0);
            $table->decimal('montant', 15, 2)->default(0);
            $table->text('observations')->nullable();
            $table->foreignId('cree_par')->nullable();
            $table->foreignId('modifie_par')->nullable();
            $table->tinyInteger('etat')->default(1)->comment('1=actif, 0=inactif, -1=supprime');
            $table->timestamps();

            $table->unique(['periode_id', 'salarie_id', 'rubrique_id'], 'saisies_paie_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saisies_paie');
    }
};