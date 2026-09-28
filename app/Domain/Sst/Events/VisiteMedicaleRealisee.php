<?php

namespace App\Domain\Sst\Events;

use App\Domain\Sst\Models\VisiteMedicale;
use Illuminate\Foundation\Events\Dispatchable;

class VisiteMedicaleRealisee
{
    use Dispatchable;

    public function __construct(public VisiteMedicale $visite) {}
}