<?php

use App\Domain\Paie\Services\CalculateurSoldeToutCompte;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Paie de sortie — rubriques du solde de tout compte, créées pour chaque entreprise existante.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (DB::table('entreprises')->pluck('id') as $entrepriseId) {
            CalculateurSoldeToutCompte::installerRubriques($entrepriseId);
        }
    }

    public function down(): void
    {
        DB::table('rubriques_paie')->whereIn('code', array_keys(CalculateurSoldeToutCompte::RUBRIQUES))->delete();
    }
};
