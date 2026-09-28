<?php

namespace App\Domain\Sst\Events;

use App\Domain\Sst\Models\EvenementSecurite;
use Illuminate\Foundation\Events\Dispatchable;

class EvenementSecuriteCloture
{
    use Dispatchable;

    public function __construct(public EvenementSecurite $evenement) {}
}