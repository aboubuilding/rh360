<?php

namespace App\Domain\Carriere\Actions;

use App\Domain\Carriere\Models\MouvementCarriere;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProlongerInterim
{
    public function executer(MouvementCarriere $interim, string $nouvelleDateFin, ?string $motif = null): MouvementCarriere
    {
        if ($interim->type_mouvement->value !== 'interim') {
            throw new \DomainException('Ce mouvement n\'est pas un intérim.');
        }

        if ($interim->statut->estFinal()) {
            throw new \DomainException('Cet intérim est déjà clôturé.');
        }

        $nouvelleFin = Carbon::parse($nouvelleDateFin);
        if ($interim->date_fin_prevue && $nouvelleFin->lte($interim->date_fin_prevue)) {
            throw new \DomainException('La nouvelle date de fin doit être postérieure à la date actuelle.');
        }

        return DB::transaction(function () use ($interim, $nouvelleFin, $motif) {
            $ancienne = $interim->date_fin_prevue;

            $interim->update([
                'date_fin_prevue' => $nouvelleFin,
            ]);

            app(\App\Domain\Administration\Services\ServiceAudit::class)->tracer(
                'carriere.interim.prolonge',
                $interim,
                [
                    'ancienne_fin' => $ancienne?->format('Y-m-d'),
                    'nouvelle_fin' => $nouvelleFin->format('Y-m-d'),
                    'motif' => $motif,
                ]
            );

            return $interim->fresh();
        });
    }
}