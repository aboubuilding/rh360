<?php

namespace App\Domain\Carriere\Actions;

use App\Domain\Carriere\Enums\StatutMouvement;
use App\Domain\Carriere\Models\MouvementCarriere;
use App\Domain\Carriere\Services\CircuitMouvement;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

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

            // Le poste permanent reprend son occupant habituel : on ferme
            // l'affectation temporaire en cours et on en recrée une sur le
            // poste permanent s'il y en avait un.
            \App\Domain\Personnel\Models\Affectation::query()
                ->where('salarie_id', $interim->salarie_id)
                ->where('en_cours', true)
                ->update([
                    'en_cours' => false,
                    'date_fin' => Carbon::parse($dateFinReelle),
                ]);

            return $this->circuit->transitionner(
                $interim,
                StatutMouvement::TERMINE,
                'interim.cloture',
                $motif,
            );
        });
    }
}