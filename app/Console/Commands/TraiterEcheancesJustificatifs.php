<?php

namespace App\Console\Commands;

use App\Domain\Administration\Models\Entreprise;
use App\Domain\Conges\Services\GestionJustificatifs;
use Illuminate\Console\Command;

class TraiterEcheancesJustificatifs extends Command
{
    protected $signature = 'demandes:echeances {--entreprise= : ID d\'une entreprise spécifique}';

    protected $description = 'Requalifie les demandes dont le justificatif n\'a pas été remis dans le délai';

    public function handle(GestionJustificatifs $justificatifs): int
    {
        $entreprises = $this->option('entreprise')
            ? Entreprise::where('id', $this->option('entreprise'))->get()
            : Entreprise::where('actif', true)->get();

        foreach ($entreprises as $entreprise) {
            $nombre = $justificatifs->traiterEcheances($entreprise->id);
            $this->info("Entreprise {$entreprise->id} : {$nombre} demande(s) requalifiée(s).");
        }

        return self::SUCCESS;
    }
}
