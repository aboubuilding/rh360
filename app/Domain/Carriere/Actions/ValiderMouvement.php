<?php

namespace App\Domain\Carriere\Actions;

use App\Domain\Carriere\Enums\StatutMouvement;
use App\Domain\Carriere\Events\MouvementValide;
use App\Domain\Carriere\Models\MouvementCarriere;
use App\Domain\Carriere\Services\CircuitMouvement;

class ValiderMouvement
{
    public function __construct(private CircuitMouvement $circuit) {}

    public function executer(MouvementCarriere $mouvement, ?string $note = null): MouvementCarriere
    {
        $mouvement->update([
            'date_decision' => now(),
            'valide_par' => auth()->id(),
        ]);

        $mouvement = $this->circuit->transitionner(
            $mouvement,
            StatutMouvement::VALIDE,
            'validation',
            $note,
        );

        event(new MouvementValide($mouvement, $note));

        return $mouvement;
    }
}