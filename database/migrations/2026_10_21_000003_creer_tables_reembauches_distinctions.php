<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * - periodes_emploi_anterieures : emplois précédents du salarié dans l'entreprise et motif du départ ;
 *   ils se cumulent à l'ancienneté si le départ est d'un motif prévu (CCIT art. 40) ;
 * - distinctions_honorifiques : gratifications à 10, 15, 20, 25, 30 et 35 ans de service (CCIT art. 64).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('periodes_emploi_anterieures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id')->constrained('entreprises')->restrictOnDelete();
            $table->foreignId('salarie_id')->constrained('salaries')->restrictOnDelete();
            $table->date('date_debut');
            $table->date('date_fin');
            $table->string('motif_depart', 40);
            $table->string('chemin_piece', 500)->nullable();
            $table->foreignId('cree_par')->nullable()->constrained('utilisateurs')->nullOnDelete();
            $table->tinyInteger('etat')->default(1)->index()->comment('1=actif, 0=inactif, -1=supprime');
            $table->timestamps();
        });

        Schema::create('distinctions_honorifiques', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id')->constrained('entreprises')->restrictOnDelete();
            $table->foreignId('salarie_id')->constrained('salaries')->restrictOnDelete();
            $table->unsignedSmallInteger('palier_annees');
            $table->date('date_anniversaire');
            $table->decimal('mois', 4, 1);
            $table->unsignedInteger('base')->comment('FCFA');
            $table->unsignedInteger('prime_anciennete')->comment('FCFA');
            $table->unsignedInteger('montant')->comment('FCFA');
            $table->string('statut', 20)->default('due');
            $table->foreignId('periode_paie_id')->nullable()->constrained('periodes_paie')->nullOnDelete();
            $table->foreignId('decide_par')->nullable()->constrained('utilisateurs')->nullOnDelete();
            $table->tinyInteger('etat')->default(1)->index()->comment('1=actif, 0=inactif, -1=supprime');
            $table->timestamps();

            $table->unique(['salarie_id', 'palier_annees'], 'distinctions_salarie_palier_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('distinctions_honorifiques');
        Schema::dropIfExists('periodes_emploi_anterieures');
    }
};
