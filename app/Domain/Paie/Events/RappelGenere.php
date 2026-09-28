<?php

namespace App\Domain\Paie\Events;

use App\Domain\Paie\Models\RappelAvancement;
use Illuminate\Foundation\Events\Dispatchable;

class RappelGenere
{
    use Dispatchable;

    public function __construct(public RappelAvancement $rappel) {}
}