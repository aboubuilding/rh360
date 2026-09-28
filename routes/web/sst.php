<?php

use App\Http\Controllers\Sst\DotationEpiController;
use App\Http\Controllers\Sst\EvenementSecuriteController;
use App\Http\Controllers\Sst\HabilitationController;
use App\Http\Controllers\Sst\ReportingSstController;
use App\Http\Controllers\Sst\RisqueController;
use App\Http\Controllers\Sst\VisiteMedicaleController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('sst')->name('sst.')->group(function () {

    // ============================================================
    // 1. ROUTES FIXES
    // ============================================================

    // Reporting (avant {visite} pour éviter les conflits)
    Route::get('reporting', [ReportingSstController::class, 'index'])->name('reporting.index');
    Route::get('reporting/exporter', [ReportingSstController::class, 'exporter'])->name('reporting.exporter');

    // Visites médicales
    Route::prefix('visites')->name('visites.')->group(function () {
        Route::get('/', [VisiteMedicaleController::class, 'index'])->name('index');
        Route::get('creer', [VisiteMedicaleController::class, 'create'])->name('create');
        Route::post('/', [VisiteMedicaleController::class, 'store'])->name('store');
        Route::post('{visite}/renseigner', [VisiteMedicaleController::class, 'renseigner'])->name('renseigner');
        Route::post('{visite}/annuler', [VisiteMedicaleController::class, 'annuler'])->name('annuler');
        Route::delete('{visite}', [VisiteMedicaleController::class, 'destroy'])->name('destroy');
        Route::get('{visite}', [VisiteMedicaleController::class, 'show'])->name('show');
    });

    // Événements sécurité
    Route::prefix('evenements')->name('evenements.')->group(function () {
        Route::get('/', [EvenementSecuriteController::class, 'index'])->name('index');
        Route::get('creer', [EvenementSecuriteController::class, 'create'])->name('create');
        Route::post('/', [EvenementSecuriteController::class, 'store'])->name('store');
        Route::post('{evenement}/cloturer', [EvenementSecuriteController::class, 'cloturer'])->name('cloturer');
        Route::post('{evenement}/annuler', [EvenementSecuriteController::class, 'annuler'])->name('annuler');
        Route::post('{evenement}/actions', [EvenementSecuriteController::class, 'ajouterAction'])->name('actions.store');
        Route::delete('{evenement}', [EvenementSecuriteController::class, 'destroy'])->name('destroy');
        Route::get('{evenement}', [EvenementSecuriteController::class, 'show'])->name('show');
    });

    // Risques
    Route::prefix('risques')->name('risques.')->group(function () {
        Route::get('/', [RisqueController::class, 'index'])->name('index');
        Route::get('creer', [RisqueController::class, 'create'])->name('create');
        Route::post('/', [RisqueController::class, 'store'])->name('store');
        Route::post('{risque}/evaluer', [RisqueController::class, 'evaluer'])->name('evaluer');
        Route::post('{risque}/actions', [RisqueController::class, 'ajouterAction'])->name('actions.store');
        Route::post('{risque}/archiver', [RisqueController::class, 'archiver'])->name('archiver');
        Route::delete('{risque}', [RisqueController::class, 'destroy'])->name('destroy');
        Route::get('{risque}', [RisqueController::class, 'show'])->name('show');
    });

    // EPI
    Route::prefix('epi')->name('epi.')->group(function () {
        Route::get('/', [DotationEpiController::class, 'index'])->name('index');
        Route::get('creer', [DotationEpiController::class, 'create'])->name('create');
        Route::post('/', [DotationEpiController::class, 'store'])->name('store');
        Route::post('{epi}/operations', [DotationEpiController::class, 'ajouterOperation'])->name('operations.store');
        Route::post('{epi}/operations/{operation}/annuler', [DotationEpiController::class, 'annulerOperation'])->name('operations.annuler');
        Route::delete('{epi}', [DotationEpiController::class, 'destroy'])->name('destroy');
        Route::get('{epi}', [DotationEpiController::class, 'show'])->name('show');
    });

    // Habilitations
    Route::prefix('habilitations')->name('habilitations.')->group(function () {
        Route::get('/', [HabilitationController::class, 'index'])->name('index');
        Route::get('creer', [HabilitationController::class, 'create'])->name('create');
        Route::post('/', [HabilitationController::class, 'store'])->name('store');
        Route::post('{habilitation}/renouveler', [HabilitationController::class, 'renouveler'])->name('renouveler');
        Route::post('{habilitation}/revoquer', [HabilitationController::class, 'revoquer'])->name('revoquer');
        Route::delete('{habilitation}', [HabilitationController::class, 'destroy'])->name('destroy');
        Route::get('{habilitation}', [HabilitationController::class, 'show'])->name('show');
    });
});