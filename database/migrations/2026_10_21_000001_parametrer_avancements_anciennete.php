<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Avancements et ancienneté — paramétrage des points d'interprétation (colonnes additives).
 * - parametres_avancement : surcharges, par entreprise, de config/avancement.php ;
 * - regles_anciennete : mode de calcul du taux et année de début de l'incrément annuel.
 *   Les règles existantes gardent leur mode « lineaire », leur résultat ne change pas ;
 * - absences : date de remplacement du salarié absent pour maladie (fin du décompte dans l'ancienneté).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parametres_avancement', function (Blueprint $table) {
            $table->foreignId('entreprise_id')->primary()->constrained('entreprises')->restrictOnDelete();
            $table->json('valeurs');
            $table->integer('revision')->default(1);
            $table->foreignId('modifie_par')->nullable()->constrained('utilisateurs')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('regles_anciennete', function (Blueprint $table) {
            $table->string('mode_taux', 30)->default('lineaire')->after('taux_max');
            $table->unsignedSmallInteger('annee_debut_increment')->nullable()->after('mode_taux');
        });

        Schema::table('absences', function (Blueprint $table) {
            $table->date('date_remplacement')->nullable()->after('fin_le');
        });
    }

    public function down(): void
    {
        Schema::table('absences', function (Blueprint $table) {
            $table->dropColumn('date_remplacement');
        });

        Schema::table('regles_anciennete', function (Blueprint $table) {
            $table->dropColumn(['mode_taux', 'annee_debut_increment']);
        });

        Schema::dropIfExists('parametres_avancement');
    }
};
