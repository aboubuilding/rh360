<?php

namespace App\Providers;

use App\Domain\Carriere\Events\MouvementApplique;
use App\Domain\Carriere\Events\MouvementRejete;
use App\Domain\Carriere\Events\MouvementValide;
use App\Domain\Carriere\Listeners\NotifierMouvementApplique;
use App\Domain\Carriere\Listeners\PreparerProgrammation;
use App\Domain\Contrats\Events\ContratSigne;

use App\Domain\Contrats\Events\ContratValide;
use App\Domain\Contrats\Events\EvenementEssaiValide;
use App\Domain\Contrats\Listeners\CreerAlerteApresValidation;
use App\Domain\Contrats\Listeners\NotifierContratSigne;
use App\Domain\Contrats\Listeners\RecalculerEssaiApresEvenement;

use App\Domain\Formation\Events\BesoinFormationValide;
use App\Domain\Formation\Listeners\TracerValidationBesoin;
use App\Domain\Paie\Events\BulletinCalcule;
use App\Domain\Paie\Events\PeriodeValidee;


use App\Domain\Paie\Listeners\TracerValidationPeriode;
use App\Domain\Paie\Listeners\VerifierAnomaliesBulletin;
use App\Domain\Performance\Events\EntretienValide;
use App\Domain\Performance\Listeners\TracerValidationEntretien;
use App\Domain\Recrutement\Events\CandidatRetenu;
use App\Domain\Recrutement\Listeners\NotifierCandidatRetenu;

use App\Domain\Sst\Listeners\ProgrammerProchaineVisite;
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

     BesoinFormationValide::class => [
        TracerValidationBesoin::class,
    ],
    EntretienValide::class => [
        TracerValidationEntretien::class,
    ],
    CandidatRetenu::class => [
        NotifierCandidatRetenu::class,
    ],



    ];
}