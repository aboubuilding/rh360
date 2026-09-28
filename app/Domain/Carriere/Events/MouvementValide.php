<?php

namespace App\Domain\Carriere\Events;

use App\Domain\Carriere\Models\MouvementCarriere;
use Illuminate\Foundation\Events\Dispatchable;

class MouvementValide
{
    use Dispatchable;

    public function __construct(
        public MouvementCarriere $mouvement,
        public ?string $note = null,
    ) {}
}