<?php

namespace App\Domain\Carriere\Events;

use App\Domain\Carriere\Models\MouvementCarriere;
use Illuminate\Foundation\Events\Dispatchable;

class MouvementApplique
{
    use Dispatchable;

    public function __construct(public MouvementCarriere $mouvement) {}
}