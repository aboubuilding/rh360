<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


Schedule::command('rh:balayer-alertes-contrats')->dailyAt('06:00');

// Lot 4 — Application des mouvements de carrière
Schedule::command('rh:appliquer-mouvements-carriere')->dailyAt('05:30');


// Lot 5 — Recalcul annuel des soldes de congés (1er janvier à minuit)
Schedule::command('rh:recalculer-soldes-conges')
    ->yearlyOn(1, 1, '00:30');

    // Lot 6 — Calcul paie : le 25 de chaque mois à 22 h (préparation fin de mois)
Schedule::command('rh:calculer-paie-mensuelle')->monthlyOn(25, '22:00');

// Lot 7 — SST
Schedule::command('rh:verifier-echeances-sst')->dailyAt('07:00');
Schedule::command('rh:marquer-habilitations-expirees')->dailyAt('04:00');

// Lot 8 — Développement RH
Schedule::command('rh:signaler-echeances-developpement')->weeklyOn(1, '08:00');