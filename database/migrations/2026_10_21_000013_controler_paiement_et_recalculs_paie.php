<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Paie — clôture (colonnes et tables additives).
 * - periodes_paie.date_paiement : au plus 8 jours après la fin du mois, jamais un jour de repos
 *   (Code art. 163, 164) ;
 * - journal_recalculs_paie : écarts entre un bulletin et son recalcul.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('periodes_paie', function (Blueprint $table) {
            $table->date('date_paiement')->nullable()->after('statut');
        });

        Schema::create('journal_recalculs_paie', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id')->constrained('entreprises')->restrictOnDelete();
            $table->foreignId('periode_id')->constrained('periodes_paie')->cascadeOnDelete();
            $table->foreignId('salarie_id')->constrained('salaries')->restrictOnDelete();
            $table->decimal('ancien_brut', 15, 2);
            $table->decimal('ancien_net', 15, 2);
            $table->decimal('nouveau_brut', 15, 2);
            $table->decimal('nouveau_net', 15, 2);
            $table->foreignId('auteur_id')->nullable()->constrained('utilisateurs')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_recalculs_paie');
        Schema::table('periodes_paie', fn (Blueprint $t) => $t->dropColumn('date_paiement'));
    }
};
