<?php

namespace App\Domain\Paie\Actions;

use App\Domain\Interim\Services\ControleClotureInterims;
use App\Domain\Paie\Enums\StatutPeriode;
use App\Domain\Paie\Events\PeriodeValidee;
use App\Domain\Paie\Models\PeriodePaie;
use Illuminate\Support\Facades\DB;

class ValiderPeriode
{
    public function executer(PeriodePaie $periode): PeriodePaie
    {
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

        return DB::transaction(function () use ($periode) {
            $periode->update([
                'statut' => StatutPeriode::VALIDEE->value,
                'valide_le' => now(),
                'valide_par' => auth()->id(),
            ]);

            event(new PeriodeValidee($periode));

            return $periode->fresh();
        });
    }
}