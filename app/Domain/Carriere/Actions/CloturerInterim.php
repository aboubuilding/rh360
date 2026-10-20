<?php

namespace App\Domain\Carriere\Actions;

use App\Domain\Carriere\Enums\StatutMouvement;
use App\Domain\Carriere\Models\MouvementCarriere;
use App\Domain\Carriere\Services\CircuitMouvement;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * @deprecated Intérims antérieurs à la refonte, non repris dans interims_internes.
 *             Utiliser App\Domain\Interim\Services\InterimInterneService.
 */
class CloturerInterim
{
    public function __construct(private CircuitMouvement $circuit) {}

    public function executer(MouvementCarriere $interim, string $dateFinReelle, ?string $motif = null): MouvementCarriere
    {
        if ($interim->type_mouvement->value !== 'interim') {
            throw new \DomainException('Ce mouvement n\'est pas un intérim.');
        }

        return DB::transaction(function () use ($interim, $dateFinReelle, $motif) {
            $interim->update([
                'date_fin_reelle' => Carbon::parse($dateFinReelle),
                'motif_cloture' => $motif,
            ]);

            // RG-INT-08 — L'intérim ne crée pas d'affectation (AppliqueurMouvement) : l'affectation
            // en cours est le poste permanent du salarié, qu'il retrouve. Elle n'est donc plus fermée.

            return $this->circuit->transitionner(
                $interim,
                StatutMouvement::TERMINE,
                'interim.cloture',
                $motif,
            );
        });
    }
}
