<?php

use App\Http\Controllers\Personnel\DocumentSalarieController;
use App\Http\Controllers\Personnel\FusionSalarieController;
use App\Http\Controllers\Personnel\ImportSalarieController;
use App\Http\Controllers\Personnel\MembreFoyerController;
use App\Http\Controllers\Personnel\SalarieController;
use App\Http\Controllers\Personnel\WizardSalarieController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('salaries')->name('personnel.salaries.')->group(function () {

    // ============================================================
    // 1. ROUTES FIXES (priorité absolue)
    // ============================================================

    Route::get('/', [SalarieController::class, 'index'])->name('index');

    // --- Wizard de création (5 étapes) ---
    Route::prefix('creer')->name('wizard.')->group(function () {
        Route::get('/', [WizardSalarieController::class, 'demarrer'])->name('demarrer');
        Route::get('etape/{numero}', [WizardSalarieController::class, 'etape'])->name('etape');
        Route::post('etape/{numero}', [WizardSalarieController::class, 'enregistrerEtape'])->name('etape.store');
        Route::get('recapitulatif', [WizardSalarieController::class, 'recapitulatif'])->name('recapitulatif');
        Route::post('valider', [WizardSalarieController::class, 'valider'])->name('valider');
        Route::post('abandonner', [WizardSalarieController::class, 'abandonner'])->name('abandonner');
    });

    // --- Import Excel ---
    Route::get('import', [ImportSalarieController::class, 'formulaire'])->name('import');
    Route::get('import/modele', [ImportSalarieController::class, 'modele'])->name('import.modele');
    Route::post('import/apercu', [ImportSalarieController::class, 'apercu'])->name('import.apercu');
    Route::post('import/executer', [ImportSalarieController::class, 'importer'])->name('import.executer');

    // ============================================================
    // 2. SALARIÉ (routes avec paramètre)
    // ============================================================

    Route::post('/', [SalarieController::class, 'store'])->name('store');
    Route::get('{salarie}/photo', [SalarieController::class, 'photo'])->name('photo');
    Route::get('{salarie}/modifier', [SalarieController::class, 'edit'])->name('edit');
    Route::put('{salarie}', [SalarieController::class, 'update'])->name('update');
    Route::delete('{salarie}', [SalarieController::class, 'destroy'])->name('destroy');
    Route::get('{salarie}', [SalarieController::class, 'show'])->name('show');

    // ============================================================
    // 3. SOUS-RESSOURCES (avec scopeBindings)
    // ============================================================

    // Fusion
    Route::get('{source}/fusionner', [FusionSalarieController::class, 'formulaire'])->name('fusion');
    Route::post('{source}/fusionner', [FusionSalarieController::class, 'fusionner'])->name('fusion.store');

    // --- Membres du foyer (scopeBindings) ---
    Route::prefix('{salarie}/foyer')->name('foyer.')->scopeBindings()->group(function () {
        Route::post('/', [MembreFoyerController::class, 'store'])->name('store');
        Route::put('{membre}', [MembreFoyerController::class, 'update'])->name('update');
        Route::post('{membre}/archiver', [MembreFoyerController::class, 'archiver'])->name('archiver');
        Route::post('{membre}/restaurer', [MembreFoyerController::class, 'restaurer'])->name('restaurer');
        Route::delete('{membre}', [MembreFoyerController::class, 'destroy'])->name('destroy');
    });

    // --- Documents (scopeBindings) ---
    Route::prefix('{salarie}/documents')->name('documents.')->scopeBindings()->group(function () {
        Route::post('/', [DocumentSalarieController::class, 'store'])->name('store');
        Route::get('{document}/voir', [DocumentSalarieController::class, 'voir'])->name('voir');
        Route::post('{document}/renouveler', [DocumentSalarieController::class, 'renouveler'])->name('renouveler');
        Route::post('{document}/archiver', [DocumentSalarieController::class, 'archiver'])->name('archiver');
        Route::delete('{document}', [DocumentSalarieController::class, 'destroy'])->name('destroy');
    });
});