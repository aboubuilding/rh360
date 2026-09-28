<?php

namespace App\Console\Commands;

use App\Domain\Administration\Models\Entreprise;
use App\Domain\Contrats\Services\GenerateurAlertesContrats;
use Illuminate\Console\Command;

class BalayerAlertesContrats extends Command
{
    protected $signature = 'rh:balayer-alertes-contrats {--entreprise= : ID d\'une entreprise spécifique}';
    protected $description = 'Génère et resynchronise les alertes contractuelles';

    public function handle(GenerateurAlertesContrats $service): int
    {
        $entrepriseId = $this->option('entreprise');

        $entreprises = $entrepriseId
            ? Entreprise::where('id', $entrepriseId)->get()
            : Entreprise::where('actif', true)->get();

        $total = 0;
        foreach ($entreprises as $entreprise) {
            $count = $service->balayer($entreprise->id);
            $this->info("Entreprise {$entreprise->id} — {$count} contrats traités.");
            $total += $count;
        }

        $this->info("Total : {$total} contrats balayés.");
        return self::SUCCESS;
    }
}