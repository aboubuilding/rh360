<?php

namespace App\Domain\Performance\Events;

use App\Domain\Performance\Models\EntretienEvaluation;
use Illuminate\Foundation\Events\Dispatchable;

class EntretienValide
{
    use Dispatchable;

    public function __construct(public EntretienEvaluation $entretien) {}
}