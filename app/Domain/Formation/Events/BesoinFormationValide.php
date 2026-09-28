<?php

namespace App\Domain\Formation\Events;

use App\Domain\Formation\Models\BesoinFormation;
use Illuminate\Foundation\Events\Dispatchable;

class BesoinFormationValide
{
    use Dispatchable;

    public function __construct(public BesoinFormation $besoin) {}
}