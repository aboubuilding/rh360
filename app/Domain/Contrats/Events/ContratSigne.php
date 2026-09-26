<?php

namespace App\Domain\Contrats\Events;

use App\Domain\Contrats\Models\Contrat;
use Illuminate\Foundation\Events\Dispatchable;

class ContratSigne
{
    use Dispatchable;

    public function __construct(public Contrat $contrat) {}
}