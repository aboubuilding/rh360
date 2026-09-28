<?php

namespace App\Domain\Paie\Actions;

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