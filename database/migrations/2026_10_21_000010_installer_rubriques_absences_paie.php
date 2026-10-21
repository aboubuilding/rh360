<?php

use App\Domain\Paie\Services\PreparateurPaieAbsences;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Paie — rubriques des absences (neutralisations du salaire et maintiens : congé payé, maladie,
 * maternité, absences non rémunérées), créées pour chaque entreprise existante.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (DB::table('entreprises')->pluck('id') as $entrepriseId) {
            PreparateurPaieAbsences::installerRubriques($entrepriseId);
        }
    }

    public function down(): void
    {
        DB::table('rubriques_paie')->whereIn('code', array_keys(PreparateurPaieAbsences::rubriquesNecessaires()))->delete();
    }
};
