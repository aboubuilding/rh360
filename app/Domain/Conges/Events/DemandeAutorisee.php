<?php

namespace App\Domain\Conges\Events;

use App\Domain\Conges\Models\DemandeConge;
use Illuminate\Foundation\Events\Dispatchable;

class DemandeAutorisee
{
    use Dispatchable;

    public function __construct(public DemandeConge $demande) {}
}