<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Paie — modèles de bulletin appliqués aux salariés (colonnes et tables additives).
 * - elements_paie_salaries : période de validité (du … au …) et exclusion d'une rubrique du
 *   modèle pour le salarié ;
 * - affectations_modele_paie : association manuelle et datée d'un salarié à un modèle ; sans
 *   affectation, le modèle de la catégorie du salarié s'applique.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('elements_paie_salaries', function (Blueprint $table) {
            $table->date('debut')->nullable()->after('montant');
            $table->date('fin')->nullable()->after('debut');
            $table->boolean('exclu')->default(false)->after('fin');
        });

        Schema::create('affectations_modele_paie', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id')->constrained('entreprises')->restrictOnDelete();
            $table->foreignId('salarie_id')->constrained('salaries')->restrictOnDelete();
            $table->foreignId('modele_id')->constrained('modeles_paie')->restrictOnDelete();
            $table->date('debut');
            $table->date('fin')->nullable();
            $table->string('motif', 500);
            $table->foreignId('cree_par')->nullable()->constrained('utilisateurs')->nullOnDelete();
            $table->timestamps();
            $table->index(['salarie_id', 'debut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affectations_modele_paie');
        Schema::table('elements_paie_salaries', function (Blueprint $table) {
            $table->dropColumn(['debut', 'fin', 'exclu']);
        });
    }
};
