<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Paie — retenues encadrées (Code art. 120, 173 à 175 ; CCIT art. 35).
 * - rubriques_paie.type_retenue : seuls les cas légaux de retenue sont admis (prélèvement
 *   obligatoire, acompte, cession, saisie-attribution, mutuelle, cotisation syndicale,
 *   consignation conventionnelle) ;
 * - elements_paie_salaries.accord_le : accord préalable du salarié (cotisation syndicale) ;
 * - prets_salaries : prêts et avances remboursés par cession volontaire vérifiée ;
 * - saisies_attribution : saisies sur salaire notifiées à l'employeur ;
 * - lignes_bulletins.reference : prêt ou saisie remboursé par la ligne ;
 * - parametres_legaux_paie.valeur_json : barèmes (quotités cessible et saisissable).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rubriques_paie', function (Blueprint $table) {
            $table->string('type_retenue', 30)->nullable()->after('nature');
        });
        DB::table('rubriques_paie')->whereIn('code', ['CNSS', 'AMU', 'IRPP'])->update(['type_retenue' => 'obligatoire']);
        DB::table('rubriques_paie')->where('code', 'AVANCE')->update(['type_retenue' => 'acompte']);
        DB::table('rubriques_paie')->where('code', 'PRET')->update(['type_retenue' => 'cession']);

        Schema::table('elements_paie_salaries', function (Blueprint $table) {
            $table->date('accord_le')->nullable()->after('exclu');
        });

        Schema::table('lignes_bulletins', function (Blueprint $table) {
            $table->string('reference', 60)->nullable()->after('ordre');
        });

        Schema::table('parametres_legaux_paie', function (Blueprint $table) {
            $table->decimal('valeur', 15, 4)->nullable()->change();
            $table->json('valeur_json')->nullable()->after('valeur');
        });

        Schema::create('prets_salaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id')->constrained('entreprises')->restrictOnDelete();
            $table->foreignId('salarie_id')->constrained('salaries')->restrictOnDelete();
            $table->string('type', 20);
            $table->decimal('montant', 15, 2);
            $table->decimal('echeance', 15, 2);
            $table->date('premier_mois');
            $table->string('verification', 20);
            $table->date('verifie_le')->nullable();
            $table->string('piece')->nullable();
            $table->string('statut', 20)->default('actif');
            $table->string('motif', 500)->nullable();
            $table->foreignId('cree_par')->nullable()->constrained('utilisateurs')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('saisies_attribution', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id')->constrained('entreprises')->restrictOnDelete();
            $table->foreignId('salarie_id')->constrained('salaries')->restrictOnDelete();
            $table->string('creancier', 200);
            $table->string('reference_acte', 120);
            $table->date('date_acte');
            $table->decimal('montant_total', 15, 2);
            $table->unsignedSmallInteger('rang')->default(1);
            $table->string('statut', 20)->default('active');
            $table->string('piece')->nullable();
            $table->foreignId('cree_par')->nullable()->constrained('utilisateurs')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saisies_attribution');
        Schema::dropIfExists('prets_salaries');
        Schema::table('parametres_legaux_paie', fn (Blueprint $t) => $t->dropColumn('valeur_json'));
        Schema::table('lignes_bulletins', fn (Blueprint $t) => $t->dropColumn('reference'));
        Schema::table('elements_paie_salaries', fn (Blueprint $t) => $t->dropColumn('accord_le'));
        Schema::table('rubriques_paie', fn (Blueprint $t) => $t->dropColumn('type_retenue'));
    }
};
