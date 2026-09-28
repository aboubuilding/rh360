<?php

use App\Http\Controllers\Carriere\AvancementController;
use App\Http\Controllers\Carriere\InterimController;
use App\Http\Controllers\Carriere\MouvementCarriereController;
use App\Http\Controllers\Carriere\ReportingCarriereController;
use App\Http\Controllers\Carriere\SituationCarriereController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('carriere')->name('carriere.')->group(function () {

    // ============================================================
    // 1. ROUTES FIXES (priorité absolue)
    // ============================================================

    // Situations
    Route::get('situations', [SituationCarriereController::class, 'index'])->name('situations.index');
    Route::get('situations/{salarie}', [SituationCarriereController::class, 'show'])->name('situations.show');
    Route::get('situations/{salarie}/reprendre', [SituationCarriereController::class, 'reprendre'])->name('situations.reprendre');
    Route::post('situations/{salarie}/reprendre', [SituationCarriereController::class, 'enregistrerReprise'])->name('situations.reprendre.store');
    Route::post('situations/{situation}/confirmer-fiabilite', [SituationCarriereController::class, 'confirmerFiabilite'])->name('situations.confirmer-fiabilite');

    // Avancements
    Route::get('avancements', [AvancementController::class, 'index'])->name('avancements.index');
    Route::post('avancements/preparer', [AvancementController::class, 'preparerPropositions'])->name('avancements.preparer');

    // Intérims
    Route::get('interims', [InterimController::class, 'index'])->name('interims.index');
    Route::get('interims/creer', [InterimController::class, 'create'])->name('interims.create');
    Route::post('interims', [InterimController::class, 'store'])->name('interims.store');

    // Reporting
    Route::get('reporting', [ReportingCarriereController::class, 'index'])->name('reporting.index');

    // ============================================================
    // 2. MOUVEMENTS (CRUD + transitions)
    // ============================================================

    Route::get('mouvements', [MouvementCarriereController::class, 'index'])->name('mouvements.index');
    Route::get('mouvements/creer', [MouvementCarriereController::class, 'create'])->name('mouvements.create');
    Route::post('mouvements', [MouvementCarriereController::class, 'store'])->name('mouvements.store');

    // Transitions (fixes avant {mouvement})
    Route::post('mouvements/{mouvement}/soumettre', [MouvementCarriereController::class, 'soumettre'])->name('mouvements.soumettre');
    Route::post('mouvements/{mouvement}/controler', [MouvementCarriereController::class, 'controler'])->name('mouvements.controler');
    Route::post('mouvements/{mouvement}/verifier', [MouvementCarriereController::class, 'verifier'])->name('mouvements.verifier');
    Route::post('mouvements/{mouvement}/valider', [MouvementCarriereController::class, 'valider'])->name('mouvements.valider');
    Route::post('mouvements/{mouvement}/rejeter', [MouvementCarriereController::class, 'rejeter'])->name('mouvements.rejeter');
    Route::post('mouvements/{mouvement}/programmer', [MouvementCarriereController::class, 'programmer'])->name('mouvements.programmer');
    Route::post('mouvements/{mouvement}/appliquer', [MouvementCarriereController::class, 'appliquer'])->name('mouvements.appliquer');
    Route::post('mouvements/{mouvement}/cloturer', [MouvementCarriereController::class, 'cloturer'])->name('mouvements.cloturer');
    Route::post('mouvements/{mouvement}/annuler', [MouvementCarriereController::class, 'annuler'])->name('mouvements.annuler');

    // CRUD final
    Route::get('mouvements/{mouvement}/modifier', [MouvementCarriereController::class, 'edit'])->name('mouvements.edit');
    Route::put('mouvements/{mouvement}', [MouvementCarriereController::class, 'update'])->name('mouvements.update');
    Route::delete('mouvements/{mouvement}', [MouvementCarriereController::class, 'destroy'])->name('mouvements.destroy');
    Route::get('mouvements/{mouvement}', [MouvementCarriereController::class, 'show'])->name('mouvements.show');

    // ============================================================
    // 3. INTÉRIMS (avec paramètre, en dernier)
    // ============================================================

    Route::get('interims/{interim}', [InterimController::class, 'show'])->name('interims.show');
    Route::post('interims/{interim}/prolonger', [InterimController::class, 'prolonger'])->name('interims.prolonger');
    Route::post('interims/{interim}/cloturer', [InterimController::class, 'cloturer'])->name('interims.cloturer');
});