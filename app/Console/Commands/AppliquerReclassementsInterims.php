<?php

namespace App\Console\Commands;

use App\Domain\Interim\Models\InterimInterne;
use App\Domain\Interim\Services\InterimInterneService;
use Illuminate\Console\Command;

class AppliquerReclassementsInterims extends Command
{
    protected $signature = 'interims:appliquer-reclassements';

    protected $description = 'Applique à leur date d\'effet les reclassements décidés à l\'issue d\'un intérim (RG-INT-08)';

    public function handle(InterimInterneService $service): int
    {
        $total = 0;

        InterimInterne::query()
            ->whereNotNull('reclassement_mouvement_id')
            ->whereNull('reclassement_applique_le')
            ->each(function (InterimInterne $interim) use ($service, &$total) {
                if ($service->appliquerReclassementProgramme($interim)) {
                    $total++;
                }
            });

        $this->info("{$total} reclassement(s) appliqué(s).");

        return self::SUCCESS;
    }
}
