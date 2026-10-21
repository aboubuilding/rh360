<?php

namespace App\Console\Commands;

use App\Domain\Administration\Models\Entreprise;
use App\Domain\Carriere\Services\GestionnaireDistinctions;
use Illuminate\Console\Command;

class GenererDistinctions extends Command
{
    protected $signature = 'avancements:distinctions {--entreprise= : ID d\'une entreprise spécifique}';

    protected $description = 'Crée les distinctions honorifiques dues (10 à 35 ans de service) avec le montant de leur gratification';

    public function handle(GestionnaireDistinctions $gestionnaire): int
    {
        $entreprises = $this->option('entreprise')
            ? Entreprise::where('id', $this->option('entreprise'))->get()
            : Entreprise::where('actif', true)->get();

        foreach ($entreprises as $entreprise) {
            $this->info("Entreprise {$entreprise->id} — {$gestionnaire->generer($entreprise->id)} distinction(s) due(s) créée(s).");
        }

        return self::SUCCESS;
    }
}
