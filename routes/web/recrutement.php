<?php

use App\Http\Controllers\Recrutement\BesoinRecrutementController;
use App\Http\Controllers\Recrutement\CandidatController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('recrutement')->name('recrutement.')->group(function () {

    // Besoins
    Route::get('besoins', [BesoinRecrutementController::class, 'index'])->name('besoins.index');
    Route::get('besoins/creer', [BesoinRecrutementController::class, 'create'])->name('besoins.create');
    Route::post('besoins', [BesoinRecrutementController::class, 'store'])->name('besoins.store');
    Route::get('besoins/{besoin}', [BesoinRecrutementController::class, 'show'])->name('besoins.show');
    Route::post('besoins/{besoin}/valider', [BesoinRecrutementController::class, 'valider'])->name('besoins.valider');
    Route::post('besoins/{besoin}/ouvrir', [BesoinRecrutementController::class, 'ouvrir'])->name('besoins.ouvrir');
    Route::delete('besoins/{besoin}', [BesoinRecrutementController::class, 'destroy'])->name('besoins.destroy');

    // Candidats
    Route::get('candidats', [CandidatController::class, 'index'])->name('candidats.index');
    Route::get('candidats/creer', [CandidatController::class, 'create'])->name('candidats.create');
    Route::post('candidats', [CandidatController::class, 'store'])->name('candidats.store');
    Route::get('candidats/{candidat}', [CandidatController::class, 'show'])->name('candidats.show');
    Route::post('candidats/{candidat}/changer-etape', [CandidatController::class, 'changerEtape'])->name('candidats.changer-etape');
    Route::post('candidats/{candidat}/retenir', [CandidatController::class, 'retenir'])->name('candidats.retenir');
    Route::post('candidats/{candidat}/refuser', [CandidatController::class, 'refuser'])->name('candidats.refuser');
    Route::post('candidats/{candidat}/integrer', [CandidatController::class, 'integrer'])->name('candidats.integrer');
    Route::delete('candidats/{candidat}', [CandidatController::class, 'destroy'])->name('candidats.destroy');
});