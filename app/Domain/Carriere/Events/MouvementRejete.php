<?php

namespace App\Domain\Carriere\Events;

use App\Domain\Carriere\Models\MouvementCarriere;
use Illuminate\Foundation\Events\Dispatchable;

class MouvementRejete
{
    use Dispatchable;

    public function __construct(
        public MouvementCarriere $mouvement,
        public string $motif,
    ) {}
}