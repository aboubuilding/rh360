<?php

namespace App\Domain\Contrats\Events;

use App\Domain\Contrats\Models\Contrat;
use Illuminate\Foundation\Events\Dispatchable;

class ContratValide
{
    use Dispatchable;

    public function __construct(
        public Contrat $contrat,
        public ?string $noteDerogation = null,
    ) {}
}