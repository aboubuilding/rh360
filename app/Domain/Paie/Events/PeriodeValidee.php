<?php

namespace App\Domain\Paie\Events;

use App\Domain\Paie\Models\PeriodePaie;
use Illuminate\Foundation\Events\Dispatchable;

class PeriodeValidee
{
    use Dispatchable;

    public function __construct(public PeriodePaie $periode) {}
}