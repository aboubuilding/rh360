<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Refonte des intérims — colonnes additives sur les postes.
 * - categorie_classification_id : catégorie du poste, pour comparer avec celle du
 *   salarié (RG-INT-01). La colonne texte « categorie » est conservée telle quelle.
 * - travaux_dangereux : poste interdit au travail intérimaire (RG-TT-06).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('postes', function (Blueprint $table) {
            $table->foreignId('categorie_classification_id')->nullable()->after('categorie')
                ->constrained('categories_classification')->restrictOnDelete();
            $table->boolean('travaux_dangereux')->default(false)->after('effectif_cible');
        });
    }

    public function down(): void
    {
        Schema::table('postes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('categorie_classification_id');
            $table->dropColumn('travaux_dangereux');
        });
    }
};
