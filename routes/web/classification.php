<?php

use App\Http\Controllers\Classification\CategorieController;
use App\Http\Controllers\Classification\ClasseController;
use App\Http\Controllers\Classification\EchelonController;
use App\Http\Controllers\Classification\PositionController;
use App\Http\Controllers\Classification\ReferentielController;
use App\Http\Controllers\Classification\RegleEvolutionController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('admin/classification')->name('classification.')->group(function () {

    Route::resource('referentiels', ReferentielController::class)
        ->parameters(['referentiels' => 'referentiel']);
    Route::resource('categories', CategorieController::class);
    Route::resource('classes', ClasseController::class);
    Route::resource('echelons', EchelonController::class);
    Route::resource('positions', PositionController::class);
    Route::resource('regles-evolution', RegleEvolutionController::class)
        ->parameters(['regles-evolution' => 'regleEvolution']);
});