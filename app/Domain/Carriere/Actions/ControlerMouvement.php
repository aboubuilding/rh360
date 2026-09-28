<?php

namespace App\Domain\Carriere\Actions;

use App\Domain\Carriere\Enums\StatutMouvement;
use App\Domain\Carriere\Models\MouvementCarriere;
use App\Domain\Carriere\Services\CircuitMouvement;

class ControlerMouvement
{
    public function __construct(private CircuitMouvement $circuit) {}

    public function executer(MouvementCarriere $mouvement, ?string $observations = null): MouvementCarriere
    {
        $mouvement->update([
            'date_controle' => now(),
            'controle_par' => auth()->id(),
        ]);

        return $this->circuit->transitionner(
            $mouvement,
            StatutMouvement::A_VERIFIER,
            'controle',
            $observations,
        );
    }
}