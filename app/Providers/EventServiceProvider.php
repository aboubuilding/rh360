<?php

namespace App\Providers;

use App\Domain\Contrats\Events\ContratSigne;
use App\Domain\Contrats\Events\ContratValide;
use App\Domain\Contrats\Events\EvenementEssaiValide;
use App\Domain\Contrats\Listeners\CreerAlerteApresValidation;
use App\Domain\Contrats\Listeners\NotifierContratSigne;
use App\Domain\Contrats\Listeners\RecalculerEssaiApresEvenement;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        ContratValide::class => [
            CreerAlerteApresValidation::class,
        ],
        ContratSigne::class => [
            NotifierContratSigne::class,
        ],
        EvenementEssaiValide::class => [
            RecalculerEssaiApresEvenement::class,
        ],
    ];
}