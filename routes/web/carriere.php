<?php

use App\Http\Controllers\Carriere\AgenceInterimController;
use App\Http\Controllers\Carriere\AncienneteController;
use App\Http\Controllers\Carriere\AvancementController;
use App\Http\Controllers\Carriere\InterimController;
use App\Http\Controllers\Carriere\MissionInterimController;
use App\Http\Controllers\Carriere\MouvementCarriereController;
use App\Http\Controllers\Carriere\ParametreAvancementController;
use App\Http\Controllers\Carriere\ParametreInterimController;
use App\Http\Controllers\Carriere\PretMainOeuvreController;
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
    Route::post('situations/{salarie}/emplois-anterieurs', [AncienneteController::class, 'ajouterPeriode'])->name('situations.emplois-anterieurs.store');
    Route::post('avancements/distinctions/{distinction}/verser', [AncienneteController::class, 'verserDistinction'])->name('avancements.distinctions.verser');
    Route::get('situations/{salarie}/reprendre', [SituationCarriereController::class, 'reprendre'])->name('situations.reprendre');
    Route::post('situations/{salarie}/reprendre', [SituationCarriereController::class, 'enregistrerReprise'])->name('situations.reprendre.store');
    Route::post('situations/{situation}/confirmer-fiabilite', [SituationCarriereController::class, 'confirmerFiabilite'])->name('situations.confirmer-fiabilite');

    // Avancements
    Route::get('avancements', [AvancementController::class, 'index'])->name('avancements.index');
    Route::post('avancements/preparer', [AvancementController::class, 'preparerPropositions'])->name('avancements.preparer');
    Route::get('avancements/parametres', [ParametreAvancementController::class, 'edit'])->name('avancements.parametres');
    Route::put('avancements/parametres', [ParametreAvancementController::class, 'update'])->name('avancements.parametres.update');
    Route::post('avancements/parametres/reinitialiser', [ParametreAvancementController::class, 'reinitialiser'])->name('avancements.parametres.reinitialiser');

    // Intérims
    Route::get('interims', [InterimController::class, 'index'])->name('interims.index');
    Route::get('interims/creer', [InterimController::class, 'create'])->name('interims.create');
    Route::post('interims', [InterimController::class, 'store'])->name('interims.store');
    Route::get('interims/parametres', [ParametreInterimController::class, 'edit'])->name('interims.parametres');
    Route::put('interims/parametres', [ParametreInterimController::class, 'update'])->name('interims.parametres.update');
    Route::post('interims/parametres/reinitialiser', [ParametreInterimController::class, 'reinitialiser'])->name('interims.parametres.reinitialiser');

    // Travail intérimaire par agence (Code du travail, art. 60 à 63)
    Route::get('interims/agences', [AgenceInterimController::class, 'index'])->name('interims.agences.index');
    Route::post('interims/agences', [AgenceInterimController::class, 'store'])->name('interims.agences.store');
    Route::put('interims/agences/{agence}', [AgenceInterimController::class, 'update'])->name('interims.agences.update');
    Route::post('interims/agences/{agence}/factures', [AgenceInterimController::class, 'facture'])->name('interims.agences.factures.store');
    Route::get('interims/missions', [MissionInterimController::class, 'index'])->name('interims.missions.index');
    Route::get('interims/missions/creer', [MissionInterimController::class, 'create'])->name('interims.missions.create');
    Route::post('interims/missions', [MissionInterimController::class, 'store'])->name('interims.missions.store');
    Route::get('interims/missions/{mission}', [MissionInterimController::class, 'show'])->name('interims.missions.show');
    Route::post('interims/missions/{mission}/checklist', [MissionInterimController::class, 'checklist'])->name('interims.missions.checklist');
    Route::post('interims/missions/{mission}/demarrer', [MissionInterimController::class, 'demarrer'])->name('interims.missions.demarrer');
    Route::post('interims/missions/{mission}/renouveler', [MissionInterimController::class, 'renouveler'])->name('interims.missions.renouveler');
    Route::post('interims/missions/{mission}/terminer', [MissionInterimController::class, 'terminer'])->name('interims.missions.terminer');
    Route::post('interims/missions/{mission}/annuler', [MissionInterimController::class, 'annuler'])->name('interims.missions.annuler');

    // Prêt de main-d'œuvre à but non lucratif (Code du travail, art. 8)
    Route::get('interims/prets', [PretMainOeuvreController::class, 'index'])->name('interims.prets.index');
    Route::get('interims/prets/creer', [PretMainOeuvreController::class, 'create'])->name('interims.prets.create');
    Route::post('interims/prets', [PretMainOeuvreController::class, 'store'])->name('interims.prets.store');
    Route::get('interims/prets/{pret}', [PretMainOeuvreController::class, 'show'])->name('interims.prets.show');
    Route::post('interims/prets/{pret}/pieces', [PretMainOeuvreController::class, 'piece'])->name('interims.prets.piece');
    Route::post('interims/prets/{pret}/refus', [PretMainOeuvreController::class, 'refus'])->name('interims.prets.refus');
    Route::post('interims/prets/{pret}/demarrer', [PretMainOeuvreController::class, 'demarrer'])->name('interims.prets.demarrer');
    Route::post('interims/prets/{pret}/refacturations', [PretMainOeuvreController::class, 'refacturation'])->name('interims.prets.refacturation');
    Route::post('interims/prets/{pret}/retour', [PretMainOeuvreController::class, 'retour'])->name('interims.prets.retour');
    Route::post('interims/prets/{pret}/annuler', [PretMainOeuvreController::class, 'annuler'])->name('interims.prets.annuler');

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
    Route::post('interims/{interim}/decider', [InterimController::class, 'decider'])->name('interims.decider');
    Route::post('interims/{interim}/avantages', [InterimController::class, 'ajouterAvantage'])->name('interims.avantages.store');
    Route::get('interims/{interim}/decisions/{decision}/piece', [InterimController::class, 'piece'])->name('interims.piece');
});
