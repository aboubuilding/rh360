<?php

namespace App\Providers;

use App\Domain\Contrats\Events\ContratSigne;
use App\Domain\Contrats\Events\ContratValide;
use App\Domain\Contrats\Events\EvenementEssaiValide;
use App\Domain\Contrats\Listeners\CreerAlerteApresValidation;
use App\Domain\Contrats\Listeners\NotifierContratSigne;
use App\Domain\Contrats\Listeners\RecalculerEssaiApresEvenement;

use App\Domain\Carriere\Events\MouvementApplique;
use App\Domain\Carriere\Events\MouvementRejete;
use App\Domain\Carriere\Events\MouvementValide;
use App\Domain\Carriere\Listeners\NotifierMouvementApplique;
use App\Domain\Carriere\Listeners\PreparerProgrammation;

use App\Domain\Paie\Events\BulletinCalcule;
use App\Domain\Paie\Events\PeriodeValidee;
use App\Domain\Paie\Listeners\TracerValidationPeriode;
use App\Domain\Paie\Listeners\VerifierAnomaliesBulletin;

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


        MouvementValide::class => [
        PreparerProgrammation::class,
    ],
    MouvementApplique::class => [
        NotifierMouvementApplique::class,
    ],
    MouvementRejete::class => [
        // Peut-être un listener de notification aux RH
    ],

     DemandeAutorisee::class => [
        NotifierDemandeAutorisee::class,
    ],
    RepriseConfirmee::class => [
        ResynchroniserSoldeApresReprise::class,
    ],

PeriodeValidee::class => [
        TracerValidationPeriode::class,
    ],
    BulletinCalcule::class => [
        VerifierAnomaliesBulletin::class,
    ],

    VisiteMedicaleRealisee::class => [
        ProgrammerProchaineVisite::class,
    ],
    EvenementSecuriteCloture::class => [
        TracerClotureEvenement::class,
    ],



    ];
}