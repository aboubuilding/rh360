<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * - parametres_interim : surcharge, par entreprise, des paramètres de config/interim.php
 *   (valeurs par défaut = valeurs prévues par les textes).
 * - interims_internes : reclassement programmé à une date d'effet future (RG-INT-08).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parametres_interim', function (Blueprint $table) {
            $table->foreignId('entreprise_id')->primary()->constrained('entreprises')->restrictOnDelete();
            $table->json('valeurs');
            $table->integer('revision')->default(1);
            $table->foreignId('modifie_par')->nullable()->constrained('utilisateurs')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('interims_internes', function (Blueprint $table) {
            $table->foreignId('reclassement_mouvement_id')->nullable()->after('motif_cloture')
                ->constrained('mouvements_carriere')->restrictOnDelete();
            $table->unsignedInteger('reclassement_salaire_base')->nullable()->after('reclassement_mouvement_id')->comment('FCFA');
            $table->dateTime('reclassement_applique_le')->nullable()->after('reclassement_salaire_base');
        });
    }

    public function down(): void
    {
        Schema::table('interims_internes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reclassement_mouvement_id');
            $table->dropColumn(['reclassement_salaire_base', 'reclassement_applique_le']);
        });

        Schema::dropIfExists('parametres_interim');
    }
};
