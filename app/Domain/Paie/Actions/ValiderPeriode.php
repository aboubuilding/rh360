<?php

namespace App\Domain\Paie\Actions;

use App\Domain\Interim\Services\ControleClotureInterims;
use App\Domain\Paie\Enums\StatutPeriode;
use App\Domain\Paie\Events\PeriodeValidee;
use App\Domain\Conges\Services\CalendrierJoursFeries;
use App\Domain\Paie\Models\PeriodePaie;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ValiderPeriode
{
    /**
     * Valide la période. La date de paiement est au plus tard 8 jours après la fin du mois et
     * n'est ni un dimanche ni un jour férié (Code art. 163, 164) ; sans date fournie, le dernier
     * jour ouvré du mois est retenu.
     */
    public function executer(PeriodePaie $periode, ?string $datePaiement = null): PeriodePaie
    {
        $date = $this->datePaiement($periode, $datePaiement);

        if ($periode->estFigee()) {
            throw new \DomainException('Cette période est déjà validée.');
        }

        if ($periode->bulletins()->count() === 0) {
            throw new \DomainException('Impossible de valider une période sans bulletins calculés.');
        }

        // Anomalies bloquantes du calcul : bulletin non calculé, salaire sous le minimum, catégorie absente…
        $bloquantes = $periode->anomalies()->where('gravite', \App\Domain\Paie\Models\AnomaliePaie::BLOQUANTE)->count();
        if ($bloquantes > 0) {
            throw new \DomainException("Impossible de valider : {$bloquantes} anomalie(s) bloquante(s) à corriger, puis recalculer la période.");
        }

        // RG-INT-09 — un intérim à régulariser bloque la validation de la paie du mois.
        app(ControleClotureInterims::class)->verifier($periode);

        return DB::transaction(function () use ($periode, $date) {
            $periode->update([
                'date_paiement' => $date,
                'statut' => StatutPeriode::VALIDEE->value,
                'valide_le' => now(),
                'valide_par' => auth()->id(),
            ]);

            event(new PeriodeValidee($periode));

            return $periode->fresh();
        });
    }

    private function datePaiement(PeriodePaie $periode, ?string $saisie): Carbon
    {
        $calendrier = app(CalendrierJoursFeries::class);
        $finMois = Carbon::create($periode->annee, $periode->mois, 1)->endOfMonth()->startOfDay();
        $repos = fn (Carbon $d) => $d->isSunday() || $calendrier->estFerie($d, $periode->entreprise_id);

        if (! $saisie) {
            $date = $finMois->copy();
            while ($repos($date)) {
                $date->subDay();
            }

            return $date;
        }

        $date = Carbon::parse($saisie)->startOfDay();
        $limite = $finMois->copy()->addDays(8);
        if ($date->gt($limite)) {
            throw new \DomainException('Le salaire mensuel est payé au plus tard 8 jours après la fin du mois, soit le '.$limite->format('d/m/Y').' au plus tard.');
        }
        if ($date->lt($finMois->copy()->startOfMonth())) {
            throw new \DomainException('La date de paiement ne peut pas précéder la période payée.');
        }
        if ($repos($date)) {
            throw new \DomainException('Le salaire n\'est pas payé un jour de repos (dimanche ou jour férié), sauf accord du salarié : choisissez une autre date.');
        }

        return $date;
    }
}