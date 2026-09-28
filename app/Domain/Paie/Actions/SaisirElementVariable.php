<?php

namespace App\Domain\Paie\Actions;

use App\Domain\Paie\Models\PeriodePaie;
use App\Domain\Paie\Models\SaisiePaie;
use Illuminate\Support\Facades\DB;

class SaisirElementVariable
{
    public function executer(PeriodePaie $periode, int $salarieId, int $rubriqueId, float $quantite, float $taux, float $montant, ?string $observations = null): SaisiePaie
    {
        if ($periode->estFigee()) {
            throw new \DomainException('Cette période est figée : aucune saisie possible.');
        }

        return DB::transaction(function () use ($periode, $salarieId, $rubriqueId, $quantite, $taux, $montant, $observations) {
            return SaisiePaie::updateOrCreate(
                [
                    'periode_id' => $periode->id,
                    'salarie_id' => $salarieId,
                    'rubrique_id' => $rubriqueId,
                ],
                [
                    'entreprise_id' => $periode->entreprise_id,
                    'quantite' => $quantite,
                    'taux' => $taux,
                    'montant' => $montant,
                    'observations' => $observations,
                    'modifie_par' => auth()->id(),
                    'etat' => 1,
                ]
            );
        });
    }

    public function supprimer(PeriodePaie $periode, int $saisieId): void
    {
        if ($periode->estFigee()) {
            throw new \DomainException('Cette période est figée.');
        }

        SaisiePaie::where('id', $saisieId)
            ->where('periode_id', $periode->id)
            ->delete();
    }
}