<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Demandes et autorisations — durée en heures des demandes exprimées en heures (heures de
 * délégation, crédit d'heures syndical, allaitement), pour les compteurs mensuels.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('demandes_conges', function (Blueprint $table) {
            $table->decimal('duree_heures', 6, 2)->nullable()->after('duree_jours');
        });
    }

    public function down(): void
    {
        Schema::table('demandes_conges', function (Blueprint $table) {
            $table->dropColumn('duree_heures');
        });
    }
};
