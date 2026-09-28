<?php

use App\Http\Controllers\Paie\BulletinPaieController;
use App\Http\Controllers\Paie\HeureSupplementaireController;
use App\Http\Controllers\Paie\ModelePaieController;
use App\Http\Controllers\Paie\ParametreLegalController;
use App\Http\Controllers\Paie\PeriodePaieController;
use App\Http\Controllers\Paie\RappelAvancementController;
use App\Http\Controllers\Paie\RubriquePaieController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('paie')->name('paie.')->group(function () {

    // ============================================================
    // 1. ROUTES FIXES
    // ============================================================

    // Rubriques
    Route::get('rubriques', [RubriquePaieController::class, 'index'])->name('rubriques.index');
    Route::post('rubriques', [RubriquePaieController::class, 'store'])->name('rubriques.store');
    Route::put('rubriques/{rubrique}', [RubriquePaieController::class, 'update'])->name('rubriques.update');
    Route::delete('rubriques/{rubrique}', [RubriquePaieController::class, 'destroy'])->name('rubriques.destroy');

    // Modèles
    Route::get('modeles', [ModelePaieController::class, 'index'])->name('modeles.index');
    Route::get('modeles/creer', [ModelePaieController::class, 'create'])->name('modeles.create');
    Route::post('modeles', [ModelePaieController::class, 'store'])->name('modeles.store');
    Route::get('modeles/{modele}/modifier', [ModelePaieController::class, 'edit'])->name('modeles.edit');
    Route::put('modeles/{modele}', [ModelePaieController::class, 'update'])->name('modeles.update');
    Route::delete('modeles/{modele}', [ModelePaieController::class, 'destroy'])->name('modeles.destroy');

    // Heures supplémentaires
    Route::get('heures-supp', [HeureSupplementaireController::class, 'index'])->name('heures-supp.index');
    Route::get('heures-supp/creer', [HeureSupplementaireController::class, 'create'])->name('heures-supp.create');
    Route::post('heures-supp', [HeureSupplementaireController::class, 'store'])->name('heures-supp.store');
    Route::get('heures-supp/{heuresSupp}', [HeureSupplementaireController::class, 'show'])->name('heures-supp.show');
    Route::delete('heures-supp/{heuresSupp}', [HeureSupplementaireController::class, 'destroy'])->name('heures-supp.destroy');

    // Rappels d'avancement
    Route::get('rappels', [RappelAvancementController::class, 'index'])->name('rappels.index');
    Route::post('rappels/generer', [RappelAvancementController::class, 'generate'])->name('rappels.generer');

    // Paramètres légaux
    Route::get('parametres', [ParametreLegalController::class, 'index'])->name('parametres.index');
    Route::post('parametres/cotisations', [ParametreLegalController::class, 'storeCotisation'])->name('parametres.cotisations.store');
    Route::put('parametres/cotisations/{regle}', [ParametreLegalController::class, 'updateCotisation'])->name('parametres.cotisations.update');
    Route::delete('parametres/cotisations/{regle}', [ParametreLegalController::class, 'destroyCotisation'])->name('parametres.cotisations.destroy');
    Route::post('parametres/anciennete', [ParametreLegalController::class, 'storeAnciennete'])->name('parametres.anciennete.store');
    Route::put('parametres/anciennete/{regle}', [ParametreLegalController::class, 'updateAnciennete'])->name('parametres.anciennete.update');
    Route::post('parametres/irpp', [ParametreLegalController::class, 'storeIrpp'])->name('parametres.irpp.store');
    Route::put('parametres/irpp/{regle}', [ParametreLegalController::class, 'updateIrpp'])->name('parametres.irpp.update');

    // ============================================================
    // 2. PÉRIODES
    // ============================================================

    Route::get('periodes', [PeriodePaieController::class, 'index'])->name('periodes.index');
    Route::get('periodes/creer', [PeriodePaieController::class, 'create'])->name('periodes.create');
    Route::post('periodes', [PeriodePaieController::class, 'store'])->name('periodes.store');

    // Actions sur une période
    Route::post('periodes/{periode}/calculer', [PeriodePaieController::class, 'calculer'])->name('periodes.calculer');
    Route::post('periodes/{periode}/valider', [PeriodePaieController::class, 'valider'])->name('periodes.valider');
    Route::post('periodes/{periode}/reouvrir', [PeriodePaieController::class, 'reouvrir'])->name('periodes.reouvrir');
    Route::post('periodes/{periode}/saisir', [PeriodePaieController::class, 'saisir'])->name('periodes.saisir');
    Route::delete('periodes/{periode}/saisies/{saisieId}', [PeriodePaieController::class, 'destroySaisie'])->name('periodes.saisies.destroy');
    Route::get('periodes/{periode}/journal', [PeriodePaieController::class, 'journal'])->name('periodes.journal');

    // ============================================================
    // 3. BULLETINS
    // ============================================================

    Route::get('bulletins/{bulletin}', [BulletinPaieController::class, 'show'])->name('bulletins.show');
    Route::get('bulletins/{bulletin}/pdf', [BulletinPaieController::class, 'imprimer'])->name('bulletins.pdf');

    // ============================================================
    // 4. CRUD PÉRIODE (en dernier, {periode} générique)
    // ============================================================

    Route::get('periodes/{periode}', [PeriodePaieController::class, 'show'])->name('periodes.show');
    Route::delete('periodes/{periode}', [PeriodePaieController::class, 'destroy'])->name('periodes.destroy');
});