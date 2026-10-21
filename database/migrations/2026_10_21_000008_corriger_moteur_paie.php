<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Paie — corrections du moteur (colonnes et tables additives).
 * - rubriques_paie.est_frais : remboursement de frais, hors brut et hors assiettes, ajouté au net
 *   (Code art. 162) ; la prime de transport existante est marquée comme telle ;
 * - bulletins_paie : assiette des cotisations, frais, cotisations patronales, présence du mois,
 *   catégorie, échelon et emploi au moment du calcul (CCIT art. 35) ;
 * - anomalies_paie : anomalies du calcul d'une période ; les anomalies bloquantes empêchent
 *   la validation ;
 * - parametres_legaux_paie : paramètres externes datés (SMIG…).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rubriques_paie', function (Blueprint $table) {
            $table->boolean('est_frais')->default(false)->after('soumis_cotisation');
        });
        DB::table('rubriques_paie')->where('code', 'PRIME-TRANSP')->update(['est_frais' => true]);

        Schema::table('bulletins_paie', function (Blueprint $table) {
            $table->decimal('assiette_cotisations', 15, 2)->default(0)->after('montant_brut');
            $table->decimal('montant_frais', 15, 2)->default(0)->after('montant_retenues');
            $table->decimal('montant_cotisations_patronales', 15, 2)->default(0)->after('montant_net');
            $table->unsignedTinyInteger('jours_presence')->nullable()->after('montant_irpp');
            $table->unsignedTinyInteger('jours_mois')->nullable()->after('jours_presence');
            $table->string('categorie_libelle', 120)->nullable()->after('jours_mois');
            $table->string('echelon_libelle', 60)->nullable()->after('categorie_libelle');
            $table->string('emploi_libelle', 200)->nullable()->after('echelon_libelle');
        });

        Schema::create('anomalies_paie', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id')->constrained('entreprises')->restrictOnDelete();
            $table->foreignId('periode_id')->constrained('periodes_paie')->cascadeOnDelete();
            $table->foreignId('salarie_id')->nullable()->constrained('salaries')->nullOnDelete();
            $table->string('gravite', 20);
            $table->string('code', 40);
            $table->string('message', 500);
            $table->timestamp('created_at')->useCurrent();
            $table->index(['periode_id', 'gravite']);
        });

        Schema::create('parametres_legaux_paie', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id')->constrained('entreprises')->restrictOnDelete();
            $table->string('cle', 60);
            $table->decimal('valeur', 15, 4);
            $table->date('debut_effet');
            $table->string('commentaire', 255)->nullable();
            $table->timestamps();
            $table->unique(['entreprise_id', 'cle', 'debut_effet']);
        });

        // SMIG : 52 500 FCFA, niveau de la catégorie E1 du barème 2026 (à confirmer par l'arrêté en vigueur).
        foreach (DB::table('entreprises')->pluck('id') as $entrepriseId) {
            DB::table('parametres_legaux_paie')->insert([
                'entreprise_id' => $entrepriseId, 'cle' => 'smig', 'valeur' => 52500, 'debut_effet' => '2026-05-20',
                'commentaire' => 'Niveau E1 du barème de la CCIT 2026 — à confirmer par l\'arrêté en vigueur',
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('parametres_legaux_paie');
        Schema::dropIfExists('anomalies_paie');
        Schema::table('bulletins_paie', function (Blueprint $table) {
            $table->dropColumn([
                'assiette_cotisations', 'montant_frais', 'montant_cotisations_patronales',
                'jours_presence', 'jours_mois', 'categorie_libelle', 'echelon_libelle', 'emploi_libelle',
            ]);
        });
        Schema::table('rubriques_paie', function (Blueprint $table) {
            $table->dropColumn('est_frais');
        });
    }
};
