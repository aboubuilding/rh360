<?php

namespace App\Domain\Paie\Events;

use App\Domain\Paie\Models\HeureSupplementaire;
use Illuminate\Foundation\Events\Dispatchable;

class HeuresSuppAjoutees
{
    use Dispatchable;

    public function __construct(public HeureSupplementaire $acte) {}
}