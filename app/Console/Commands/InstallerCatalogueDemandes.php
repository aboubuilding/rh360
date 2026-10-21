<?php

namespace App\Console\Commands;

use App\Domain\Administration\Models\Entreprise;
use App\Domain\Conges\Services\InstallateurCatalogueDemandes;
use Illuminate\Console\Command;

class InstallerCatalogueDemandes extends Command
{
    protected $signature = 'demandes:installer-catalogue
        {--entreprise= : ID d\'une entreprise spécifique}
        {--aligner : Remplace aussi les réglages des types et événements existants par ceux des textes}';

    protected $description = 'Installe les types de demande et les événements de permission exceptionnelle — idempotent';

    public function handle(InstallateurCatalogueDemandes $installateur): int
    {
        $entreprises = $this->option('entreprise')
            ? Entreprise::where('id', $this->option('entreprise'))->get()
            : Entreprise::where('actif', true)->get();

        foreach ($entreprises as $entreprise) {
            $r = $installateur->installer($entreprise->id, (bool) $this->option('aligner'));
            $this->info("Entreprise {$entreprise->id} : {$r['crees']} type(s) créé(s), {$r['completes']} complété(s), {$r['alignes']} aligné(s), {$r['evenements']} événement(s) créé(s).");

            if ($r['a_verifier']) {
                $this->warn('Types sans règles à renseigner (Congés › Types de congés) : '.implode(', ', $r['a_verifier']).'.');
            }
        }

        return self::SUCCESS;
    }
}
