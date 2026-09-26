<?php

namespace App\Domain\Contrats\Events;

use App\Domain\Contrats\Models\EvenementEssai;
use Illuminate\Foundation\Events\Dispatchable;

class EvenementEssaiValide
{
    use Dispatchable;

    public function __construct(public EvenementEssai $evenement) {}
}