<?php

namespace App\Console\Commands;

use App\Domain\Administration\Models\Entreprise;
use App\Domain\Conges\Enums\FamilleDemande;
use App\Domain\Conges\Models\TypeConge;
use App\Domain\Conges\Services\GrandLivreConges;
use App\Domain\Personnel\Models\Salarie;
use Illuminate\Console\Command;

class CloturerLotsConges extends Command
{
    protected $signature = 'conges:cloturer-lots {--entreprise= : ID d\'une entreprise spécifique}';

    protected $description = 'Arrondit les lots de congés qui s\'ouvrent et traite les reliquats au-delà de la durée de report — idempotent';

    public function handle(GrandLivreConges $grandLivre): int
    {
        $entreprises = $this->option('entreprise')
            ? Entreprise::where('id', $this->option('entreprise'))->get()
            : Entreprise::where('actif', true)->get();

        foreach ($entreprises as $entreprise) {
            $types = TypeConge::pourEntreprise($entreprise->id)->where('actif', true)->where('famille', FamilleDemande::CONGE_PAYE->value)->get();
            $total = ['arrondis' => 0, 'expirations' => 0, 'a_imposer' => 0];

            foreach (Salarie::where('entreprise_id', $entreprise->id)->where('actif', true)->get() as $salarie) {
                foreach ($types as $type) {
                    $r = $grandLivre->cloturerLots($salarie, $type);
                    $total['arrondis'] += $r['arrondis'];
                    $total['expirations'] += $r['expirations'];
                    foreach ($r['a_imposer'] as $lot) {
                        $total['a_imposer']++;
                        $this->warn("{$salarie->nom_complet} : {$lot['reste']} jour(s) du lot {$lot['lot']} au-delà de la durée de report, départ à imposer.");
                    }
                }
            }

            $this->info("Entreprise {$entreprise->id} : {$total['arrondis']} arrondi(s), {$total['expirations']} expiration(s), {$total['a_imposer']} départ(s) à imposer.");
        }

        return self::SUCCESS;
    }
}
