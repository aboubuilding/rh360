<?php

use App\Http\Controllers\Contrats\AlerteContratController;
use App\Http\Controllers\Contrats\ContratController;
use App\Http\Controllers\Contrats\EvenementEssaiController;
use App\Http\Controllers\Contrats\NotificationContratController;
use App\Http\Controllers\Contrats\ParametreContratController;
use App\Http\Controllers\Contrats\PieceContratController;
use App\Http\Controllers\Contrats\RegleContratController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('contrats')->name('contrats.')->group(function () {

    // ============================================================
    // 1. ROUTES FIXES (priorité absolue)
    // ============================================================

    // Paramètres
    Route::get('parametres', [ParametreContratController::class, 'edit'])->name('parametres.edit');
    Route::put('parametres', [ParametreContratController::class, 'update'])->name('parametres.update');

    // Règles
    Route::resource('regles', RegleContratController::class)
        ->parameters(['regles' => 'regle'])
        ->except(['show', 'create', 'edit']); // création / modification dans la modale de la liste

    // Événements d'essai
    Route::get('evenements-essai', [EvenementEssaiController::class, 'index'])
        ->name('evenements-essai.index');
    // Décisions : POST uniquement (changement d'état protégé par CSRF)
    Route::post('evenements-essai/{evenement}/valider',
        [EvenementEssaiController::class, 'valider'])
        ->name('evenements-essai.valider');
    Route::post('evenements-essai/{evenement}/refuser',
        [EvenementEssaiController::class, 'refuser'])
        ->name('evenements-essai.refuser');

    // Alertes
    Route::get('alertes', [AlerteContratController::class, 'index'])->name('alertes.index');
    Route::post('alertes/{alerte}/cloturer', [AlerteContratController::class, 'cloturer'])
        ->name('alertes.cloturer');

    // Notifications
    Route::get('notifications', [NotificationContratController::class, 'index'])
        ->name('notifications.index');
    Route::post('notifications/{notification}/lue', [NotificationContratController::class, 'marquerLue'])
        ->name('notifications.lue');
    Route::post('notifications/toutes-lues', [NotificationContratController::class, 'marquerToutesLues'])
        ->name('notifications.toutes-lues');

    // ============================================================
    // 2. ROUTES CONTRATS (avec paramètre {contrat})
    // ============================================================

    Route::get('/', [ContratController::class, 'index'])->name('contrats.index');
    Route::get('creer', [ContratController::class, 'create'])->name('contrats.create');
    Route::post('/', [ContratController::class, 'store'])->name('contrats.store');

    // Avenants
    Route::get('{contrat}/avenants/creer', [ContratController::class, 'createAvenant'])
        ->name('contrats.create-avenant');
    Route::post('{contrat}/avenants', [ContratController::class, 'storeAvenant'])
        ->name('contrats.store-avenant');

    // Transitions
    Route::post('{contrat}/soumettre', [ContratController::class, 'soumettre'])
        ->name('contrats.soumettre');
    Route::post('{contrat}/valider', [ContratController::class, 'valider'])
        ->name('contrats.valider');
    Route::post('{contrat}/retourner-brouillon', [ContratController::class, 'retournerBrouillon'])
        ->name('contrats.retourner-brouillon');
    Route::post('{contrat}/annuler', [ContratController::class, 'annuler'])
        ->name('contrats.annuler');
    Route::post('{contrat}/signer', [ContratController::class, 'signer'])
        ->name('contrats.signer');

    // Pièces
    Route::post('{contrat}/pieces', [PieceContratController::class, 'store'])
        ->name('contrats.pieces.store');
    Route::get('{contrat}/pieces/{piece}/voir', [PieceContratController::class, 'voir'])
        ->name('contrats.pieces.voir');
    Route::delete('{contrat}/pieces/{piece}', [PieceContratController::class, 'destroy'])
        ->name('contrats.pieces.destroy');

    // Événements d'essai d'un contrat
    Route::get('{contrat}/evenements-essai/declarer', [EvenementEssaiController::class, 'create'])
        ->name('contrats.evenements-essai.create');
    Route::post('{contrat}/evenements-essai', [EvenementEssaiController::class, 'store'])
        ->name('contrats.evenements-essai.store');

    // CRUD final (route {contrat} générique en dernier)
    Route::get('{contrat}/modifier', [ContratController::class, 'edit'])->name('contrats.edit');
    Route::put('{contrat}', [ContratController::class, 'update'])->name('contrats.update');
    Route::delete('{contrat}', [ContratController::class, 'destroy'])->name('contrats.destroy');
    Route::get('{contrat}', [ContratController::class, 'show'])->name('contrats.show');
});