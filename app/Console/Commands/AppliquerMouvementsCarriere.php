<?php

namespace App\Console\Commands;

use App\Domain\Administration\Models\Entreprise;
use App\Domain\Carriere\Services\AppliqueurMouvement;
use Illuminate\Console\Command;

class AppliquerMouvementsCarriere extends Command
{
    protected $signature = 'rh:appliquer-mouvements-carriere
                            {--entreprise= : ID d\'une entreprise spécifique}';
    protected $description = 'Applique à leur date d\'effet les mouvements de carrière programmés';

    public function handle(AppliqueurMouvement $appliqueur): int
    {
        $entrepriseId = $this->option('entreprise');

        $entreprises = $entrepriseId
            ? Entreprise::where('id', $entrepriseId)->get()
            : Entreprise::where('actif', true)->get();

        $total = 0;
        foreach ($entreprises as $entreprise) {
            $count = $appliqueur->balayer($entreprise->id);
            if ($count > 0) {
                $this->info("Entreprise {$entreprise->id} — {$count} mouvement(s) appliqué(s).");
            }
            $total += $count;
        }

        $this->info("Total : {$total} mouvement(s) appliqué(s).");
        return self::SUCCESS;
    }
}