<?php

namespace App\Console\Commands;

use App\Domain\Interim\Services\RepriseInterims as ServiceReprise;
use Illuminate\Console\Command;

class RepriseInterims extends Command
{
    protected $signature = 'interims:reprise
                            {--dry-run : Simule la reprise sans rien écrire}
                            {--entreprise= : ID d\'une entreprise spécifique}';

    protected $description = 'Reprend les intérims saisis avant la mise en conformité dans interims_internes (rapport CSV, ne supprime rien)';

    public function handle(ServiceReprise $reprise): int
    {
        $simulation = (bool) $this->option('dry-run');
        $entreprise = $this->option('entreprise') ? (int) $this->option('entreprise') : null;

        $rapport = $reprise->executer($simulation, $entreprise);
        $chemin = $rapport->exporter();

        $this->table(
            ['Réf.', 'Salarié', 'Nouvel état', 'Anomalie'],
            array_map(fn ($l) => [$l['reference'], $l['salarie'], $l['nouvel_etat'], $l['anomalie'] ?? '—'], $rapport->lignes),
        );

        $this->info(($simulation ? '[Simulation] ' : '').count($rapport->lignes).' enregistrement(s) examiné(s), '
            .count($rapport->anomalies()).' anomalie(s) à traiter par les RH.');
        $this->info("Rapport : {$chemin}");

        return self::SUCCESS;
    }
}
