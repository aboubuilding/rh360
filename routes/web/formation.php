<?php

use App\Http\Controllers\Formation\BesoinFormationController;
use App\Http\Controllers\Formation\FormationController;
use App\Http\Controllers\Formation\ParticipantFormationController;
use App\Http\Controllers\Formation\PlanFormationController;
use App\Http\Controllers\Formation\SessionFormationController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('formation')->name('formation.')->group(function () {

    // Catalogue de formations
    Route::get('formations', [FormationController::class, 'index'])->name('formations.index');
    Route::post('formations', [FormationController::class, 'store'])->name('formations.store');
    Route::put('formations/{formation}', [FormationController::class, 'update'])->name('formations.update');
    Route::delete('formations/{formation}', [FormationController::class, 'destroy'])->name('formations.destroy');

    // Besoins
    Route::get('besoins', [BesoinFormationController::class, 'index'])->name('besoins.index');
    Route::get('besoins/creer', [BesoinFormationController::class, 'create'])->name('besoins.create');
    Route::post('besoins', [BesoinFormationController::class, 'store'])->name('besoins.store');
    Route::post('besoins/{besoin}/valider', [BesoinFormationController::class, 'valider'])->name('besoins.valider');
    Route::delete('besoins/{besoin}', [BesoinFormationController::class, 'destroy'])->name('besoins.destroy');

    // Plans
    Route::get('plans', [PlanFormationController::class, 'index'])->name('plans.index');
    Route::get('plans/creer', [PlanFormationController::class, 'create'])->name('plans.create');
    Route::post('plans', [PlanFormationController::class, 'store'])->name('plans.store');
    Route::get('plans/{plan}', [PlanFormationController::class, 'show'])->name('plans.show');
    Route::delete('plans/{plan}', [PlanFormationController::class, 'destroy'])->name('plans.destroy');

    // Sessions
    Route::get('sessions', [SessionFormationController::class, 'index'])->name('sessions.index');
    Route::get('sessions/creer', [SessionFormationController::class, 'create'])->name('sessions.create');
    Route::post('sessions', [SessionFormationController::class, 'store'])->name('sessions.store');
    Route::get('sessions/{session}', [SessionFormationController::class, 'show'])->name('sessions.show');
    Route::post('sessions/{session}/participants', [SessionFormationController::class, 'ajouterParticipant'])->name('sessions.participants.store');
    Route::delete('sessions/{session}', [SessionFormationController::class, 'destroy'])->name('sessions.destroy');

    // Participants
    Route::post('sessions/{session}/participants/{participant}/evaluer', [ParticipantFormationController::class, 'evaluer'])->name('participants.evaluer');
    Route::delete('sessions/{session}/participants/{participant}', [ParticipantFormationController::class, 'retirer'])->name('participants.destroy');
});