<?php

namespace App\Domain\Carriere\Actions;

use App\Domain\Carriere\Enums\StatutMouvement;
use App\Domain\Carriere\Models\MouvementCarriere;
use App\Domain\Carriere\Services\CircuitMouvement;

class VerifierMouvement
{
    public function __construct(private CircuitMouvement $circuit) {}

    public function executer(MouvementCarriere $mouvement, ?string $note = null): MouvementCarriere
    {
        return $this->circuit->transitionner(
            $mouvement,
            StatutMouvement::VERIFIE,
            'verification',
            $note,
        );
    }
}