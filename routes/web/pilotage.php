<?php

use App\Http\Controllers\Pilotage\NotificationContratController;
use App\Http\Controllers\Pilotage\RechercheController;
use App\Http\Controllers\Pilotage\TableauDeBordController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {

    // ============================================================
    // Tableau de bord
    // ============================================================
    // « / » est la page d'accueil publique (routes/web.php)
    Route::get('tableau-de-bord', [TableauDeBordController::class, 'index'])->name('dashboard');

    // ============================================================
    // Recherche rapide (AJAX, Ctrl+K)
    // ============================================================
    Route::prefix('recherche')->name('recherche.')->group(function () {
        Route::get('salaries', [RechercheController::class, 'salaries'])->name('salaries');
        Route::get('salaries/{salarie}/apercu', [RechercheController::class, 'apercu'])->name('apercu');
    });

    // ============================================================
    // Notifications contractuelles — endpoints AJAX pour la cloche
    // ============================================================
    Route::prefix('notifications-contrats')->name('notifications-contrats.')->group(function () {
        Route::get('compteur', [NotificationContratController::class, 'compteur'])->name('compteur');
        Route::get('dernieres', [NotificationContratController::class, 'dernieres'])->name('dernieres');
    });
});