<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Paie de sortie — sorties des salariés et éléments du solde de tout compte : date de
 * cessation, motif, préavis (effectué ou dispensé, et par qui), faute grave ou lourde, montant
 * convenu d'une rupture conventionnelle, heures de recherche d'emploi non utilisées.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sorties_salaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id')->constrained('entreprises')->restrictOnDelete();
            $table->foreignId('salarie_id')->constrained('salaries')->restrictOnDelete();
            $table->date('date_cessation');
            $table->string('motif', 40);
            $table->string('preavis', 30);
            $table->decimal('preavis_mois', 4, 2)->nullable();
            $table->string('faute', 20)->nullable();
            $table->decimal('montant_convenu', 15, 2)->nullable();
            $table->decimal('heures_recherche', 6, 2)->nullable();
            $table->boolean('exception_fin_contrat')->default(false);
            $table->string('observations', 1000)->nullable();
            $table->foreignId('cree_par')->nullable()->constrained('utilisateurs')->nullOnDelete();
            $table->timestamps();
            $table->unique(['salarie_id', 'date_cessation']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sorties_salaries');
    }
};
