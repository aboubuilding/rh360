<?php

namespace App\Console\Commands;

use App\Domain\Administration\Models\Entreprise;
use App\Domain\Conges\Enums\FamilleDemande;
use App\Domain\Conges\Models\TypeConge;
use App\Domain\Conges\Services\GrandLivreConges;
use App\Domain\Personnel\Models\Salarie;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AcquerirCongesPayes extends Command
{
    protected $signature = 'conges:acquerir
        {--entreprise= : ID d\'une entreprise spécifique}
        {--mois= : Dernier mois à acquérir (AAAA-MM), par défaut le mois écoulé}';

    protected $description = 'Acquiert les congés payés des mois échus (2,5 jours par mois de service effectif) — idempotent';

    public function handle(GrandLivreConges $grandLivre): int
    {
        $finMois = $this->option('mois')
            ? Carbon::createFromFormat('Y-m', $this->option('mois'))->endOfMonth()->startOfDay()
            : today()->subMonthNoOverflow()->endOfMonth()->startOfDay();

        $entreprises = $this->option('entreprise')
            ? Entreprise::where('id', $this->option('entreprise'))->get()
            : Entreprise::where('actif', true)->get();

        foreach ($entreprises as $entreprise) {
            $types = TypeConge::pourEntreprise($entreprise->id)->where('actif', true)->where('famille', FamilleDemande::CONGE_PAYE->value)->get();
            $jours = 0.0;
            $salaries = Salarie::where('entreprise_id', $entreprise->id)->where('actif', true)->get();

            foreach ($salaries as $salarie) {
                foreach ($types as $type) {
                    $jours += $grandLivre->acquerirJusqua($salarie, $type, $finMois);
                    $grandLivre->projeter($salarie, $type);
                }
            }

            $this->info(sprintf('Entreprise %d : %s jour(s) acquis jusqu\'au %s.', $entreprise->id, number_format($jours, 2, ',', ' '), $finMois->format('d/m/Y')));
        }

        return self::SUCCESS;
    }
}
