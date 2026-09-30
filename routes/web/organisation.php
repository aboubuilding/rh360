<?php

use App\Http\Controllers\Organisation\PosteController;
use App\Http\Controllers\Organisation\StructureController;
use App\Http\Controllers\Organisation\TypeStructureController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('admin/organisation')->name('organisation.')->group(function () {

    Route::resource('types-structures', TypeStructureController::class)
        ->except(['create', 'edit', 'show'])
        ->parameters(['types-structures' => 'typeStructure']);

    Route::get('structures/organigramme', [StructureController::class, 'organigramme'])
        ->name('structures.organigramme');
    Route::post('structures/{structure}/fusionner', [StructureController::class, 'fusionner'])
        ->name('structures.fusionner');
    Route::resource('structures', StructureController::class)
        ->except(['create', 'edit']);

    Route::resource('postes', PosteController::class)
        ->except(['create', 'edit', 'show']);
});