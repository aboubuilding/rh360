<?php

use App\Http\Controllers\Performance\CampagneEvaluationController;
use App\Http\Controllers\Performance\CritereEvaluationController;
use App\Http\Controllers\Performance\EntretienEvaluationController;
use App\Http\Controllers\Performance\ObjectifEvaluationController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('performance')->name('performance.')->group(function () {

    // Campagnes
    Route::get('campagnes', [CampagneEvaluationController::class, 'index'])->name('campagnes.index');
    Route::get('campagnes/creer', [CampagneEvaluationController::class, 'create'])->name('campagnes.create');
    Route::post('campagnes', [CampagneEvaluationController::class, 'store'])->name('campagnes.store');
    Route::get('campagnes/{campagne}', [CampagneEvaluationController::class, 'show'])->name('campagnes.show');
    Route::post('campagnes/{campagne}/cloturer', [CampagneEvaluationController::class, 'cloturer'])->name('campagnes.cloturer');
    Route::post('campagnes/{campagne}/archiver', [CampagneEvaluationController::class, 'archiver'])->name('campagnes.archiver');
    Route::delete('campagnes/{campagne}', [CampagneEvaluationController::class, 'destroy'])->name('campagnes.destroy');

    // Critères
    Route::get('criteres', [CritereEvaluationController::class, 'index'])->name('criteres.index');
    Route::post('criteres', [CritereEvaluationController::class, 'store'])->name('criteres.store');
    Route::put('criteres/{critere}', [CritereEvaluationController::class, 'update'])->name('criteres.update');
    Route::delete('criteres/{critere}', [CritereEvaluationController::class, 'destroy'])->name('criteres.destroy');

    // Objectifs
    Route::get('objectifs', [ObjectifEvaluationController::class, 'index'])->name('objectifs.index');
    Route::post('objectifs', [ObjectifEvaluationController::class, 'store'])->name('objectifs.store');
    Route::put('objectifs/{objectif}', [ObjectifEvaluationController::class, 'update'])->name('objectifs.update');
    Route::delete('objectifs/{objectif}', [ObjectifEvaluationController::class, 'destroy'])->name('objectifs.destroy');

    // Entretiens
    Route::get('entretiens', [EntretienEvaluationController::class, 'index'])->name('entretiens.index');
    Route::get('entretiens/creer', [EntretienEvaluationController::class, 'create'])->name('entretiens.create');
    Route::get('entretiens/{entretien}', [EntretienEvaluationController::class, 'show'])->name('entretiens.show');
    Route::post('entretiens/{entretien}/auto-evaluer', [EntretienEvaluationController::class, 'autoEvaluer'])->name('entretiens.auto-evaluer');
    Route::post('entretiens/{entretien}/realiser', [EntretienEvaluationController::class, 'realiser'])->name('entretiens.realiser');
    Route::post('entretiens/{entretien}/valider', [EntretienEvaluationController::class, 'valider'])->name('entretiens.valider');
    Route::delete('entretiens/{entretien}', [EntretienEvaluationController::class, 'destroy'])->name('entretiens.destroy');
});