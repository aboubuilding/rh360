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

    Route::resource('categories', CategorieController::class)
        ->except(['create', 'edit', 'show']);

    Route::resource('classes', ClasseController::class)
        ->except(['create', 'edit', 'show'])
        ->parameters(['classes' => 'classe']);

    Route::resource('echelons', EchelonController::class)
        ->except(['create', 'edit', 'show']);

    Route::resource('positions', PositionController::class)
        ->except(['show']);

    Route::resource('regles-evolution', RegleEvolutionController::class)
        ->except(['show'])
        ->parameters(['regles-evolution' => 'regleEvolution']);
});