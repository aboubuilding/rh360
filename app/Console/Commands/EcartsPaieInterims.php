<?php

namespace App\Console\Commands;

use App\Domain\Interim\Services\ControleEcartsPaieInterims;
use App\Domain\Interim\Services\RapportReprise;
use Illuminate\Console\Command;

class EcartsPaieInterims extends Command
{
    protected $signature = 'interims:ecarts-paie {--entreprise= : ID d\'une entreprise spécifique}';

    protected $description = 'Rapport des rappels et trop-perçus d\'indemnité d\'intérim sur les périodes de paie validées (lecture seule)';

    public function handle(ControleEcartsPaieInterims $controle): int
    {
        $lignes = $controle->controler($this->option('entreprise') ? (int) $this->option('entreprise') : null);

        $chemin = RapportReprise::ecrireCsv(
            'interims/rapports/ECARTS-PAIE-INTERIMS-'.now()->format('YmdHis').'.csv',
            ['periode', 'matricule', 'salarie', 'du_fcfa', 'verse_fcfa', 'ecart_fcfa', 'nature', 'detail'],
            $lignes,
        );

        $this->table(['Période', 'Salarié', 'Dû', 'Versé', 'Écart', 'Nature'],
            array_map(fn ($l) => [$l['periode'], $l['salarie'], $l['du'], $l['verse'], $l['ecart'], $l['nature']], $lignes));

        $ecarts = array_filter($lignes, fn ($l) => $l['ecart'] !== 0);
        $this->info(count($lignes).' ligne(s), '.count($ecarts).' écart(s). Aucune écriture de paie n\'a été faite.');
        $this->info("Rapport : {$chemin}");

        return self::SUCCESS;
    }
}
