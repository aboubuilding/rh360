<?php

namespace App\Console\Commands;

use App\Domain\Administration\Models\Entreprise;
use App\Domain\Interim\Services\BalayeurInterims;
use Illuminate\Console\Command;

class BalayerInterims extends Command
{
    protected $signature = 'interims:balayer {--entreprise= : ID d\'une entreprise spécifique}';

    protected $description = 'Alertes et échéances quotidiennes des intérims (J-15, 3e mois, à régulariser, maladie du titulaire)';

    public function handle(BalayeurInterims $balayeur): int
    {
        $entrepriseId = $this->option('entreprise');

        $entreprises = $entrepriseId
            ? Entreprise::where('id', $entrepriseId)->get()
            : Entreprise::where('actif', true)->get();

        foreach ($entreprises as $entreprise) {
            $bilan = $balayeur->balayer($entreprise->id);
            $this->info("Entreprise {$entreprise->id} — {$bilan['alertes']} nouvelle(s) alerte(s), "
                ."{$bilan['a_regulariser']} intérim(s) passé(s) à régulariser.");
        }

        return self::SUCCESS;
    }
}
