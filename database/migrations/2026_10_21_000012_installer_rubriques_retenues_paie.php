<?php

use App\Domain\Paie\Services\InstallateurRubriquesRetenues;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Paie — rubriques des retenues autorisées (saisie-attribution, cotisation syndicale,
 * mutuelle), créées pour chaque entreprise existante.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (DB::table('entreprises')->pluck('id') as $entrepriseId) {
            InstallateurRubriquesRetenues::installer($entrepriseId);
        }
    }

    public function down(): void
    {
        DB::table('rubriques_paie')->whereIn('code', array_keys(InstallateurRubriquesRetenues::RUBRIQUES))->delete();
    }
};
