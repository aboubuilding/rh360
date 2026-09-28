<?php

namespace App\Domain\Recrutement\Events;

use App\Domain\Recrutement\Models\Candidat;
use Illuminate\Foundation\Events\Dispatchable;

class CandidatRetenu
{
    use Dispatchable;

    public function __construct(public Candidat $candidat) {}
}