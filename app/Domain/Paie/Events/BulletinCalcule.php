<?php

namespace App\Domain\Paie\Events;

use App\Domain\Paie\Models\BulletinPaie;
use Illuminate\Foundation\Events\Dispatchable;

class BulletinCalcule
{
    use Dispatchable;

    public function __construct(public BulletinPaie $bulletin) {}
}