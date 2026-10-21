<?php

namespace App\Console\Commands;

use App\Domain\Administration\Models\Entreprise;
use App\Domain\Classification\Services\InstallateurBaremeCcit2026;
use Illuminate\Console\Command;

class InstallerBaremeCcit2026 extends Command
{
    protected $signature = 'classification:installer-bareme-2026 {--entreprise= : ID d\'une entreprise spécifique}';

    protected $description = 'Installe la classification et le barème 2026 (classes, catégories, échelons, salaires minimums) — idempotent';

    public function handle(InstallateurBaremeCcit2026 $installateur): int
    {
        $entreprises = $this->option('entreprise')
            ? Entreprise::where('id', $this->option('entreprise'))->get()
            : Entreprise::where('actif', true)->get();

        foreach ($entreprises as $entreprise) {
            $resultat = $installateur->installer($entreprise->id);
            $this->info("Entreprise {$entreprise->id} — référentiel « {$resultat['referentiel']->nom} » : {$resultat['positions']} position(s).");
        }

        return self::SUCCESS;
    }
}
