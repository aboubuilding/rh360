<?php

namespace App\Domain\Sst\Events;

use App\Domain\Sst\Models\Habilitation;
use Illuminate\Foundation\Events\Dispatchable;

class HabilitationExpireBientot
{
    use Dispatchable;

    public function __construct(public Habilitation $habilitation) {}
}