<?php

namespace App\Domain\Paie\Actions;

use App\Domain\Paie\Enums\StatutPeriode;
use App\Domain\Paie\Models\PeriodePaie;
use Illuminate\Support\Facades\DB;

class ReouvrirPeriode
{
    /**
     * Exception réservée au super admin. Traçable.
     */
    public function executer(PeriodePaie $periode, string $motif): PeriodePaie
    {
        if (! $periode->estFigee()) {
            throw new \DomainException('Cette période n\'est pas figée.');
        }

        if (empty(trim($motif))) {
            throw new \DomainException('Le motif de réouverture est obligatoire.');
        }

        return DB::transaction(function () use ($periode, $motif) {
            $periode->update([
                'statut' => StatutPeriode::CALCULEE->value,
                'valide_le' => null,
                'valide_par' => null,
            ]);

            app(\App\Domain\Administration\Services\ServiceAudit::class)->tracer(
                'paie.periode.reouverte',
                $periode,
                ['motif' => $motif]
            );

            return $periode->fresh();
        });
    }
}