<?php

use App\Http\Controllers\Conges\AbsenceController;
use App\Http\Controllers\Conges\DemandeCongeController;
use App\Http\Controllers\Conges\DossierMaterniteController;
use App\Http\Controllers\Conges\PlanningCongeController;
use App\Http\Controllers\Conges\SoldeCongeController;
use App\Http\Controllers\Conges\TypeCongeController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('conges')->name('conges.')->group(function () {

    // ============================================================
    // 1. ROUTES FIXES (priorité absolue)
    // ============================================================

    // 4.5 Types & règles
    Route::resource('types', TypeCongeController::class)
        ->parameters(['types' => 'type'])
        ->except(['show', 'create', 'edit']); // création / modification dans la modale de la liste

    // 4.4 Droits & soldes
    Route::get('soldes', [SoldeCongeController::class, 'index'])->name('soldes.index');
    Route::get('soldes/salarie/{salarie}', [SoldeCongeController::class, 'pourSalarie'])->name('soldes.pour-salarie');
    Route::post('soldes/{solde}/ajuster', [SoldeCongeController::class, 'ajuster'])->name('soldes.ajuster');
    Route::post('soldes/{solde}/resynchroniser', [SoldeCongeController::class, 'resynchroniser'])->name('soldes.resynchroniser');

    // 4.3 Absences
    Route::get('absences', [AbsenceController::class, 'index'])->name('absences.index');
    Route::get('absences/creer', [AbsenceController::class, 'create'])->name('absences.create');
    Route::post('absences', [AbsenceController::class, 'store'])->name('absences.store');
    Route::post('absences/transmettre-paie', [AbsenceController::class, 'transmettrePaie'])->name('absences.transmettre-paie');
    Route::get('absences/{absence}', [AbsenceController::class, 'show'])->name('absences.show');
    Route::post('absences/{absence}/qualifier', [AbsenceController::class, 'qualifier'])->name('absences.qualifier');
    Route::post('absences/{absence}/regulariser', [AbsenceController::class, 'regulariser'])->name('absences.regulariser');
    Route::delete('absences/{absence}', [AbsenceController::class, 'destroy'])->name('absences.destroy');

    // 4.2 Maternité & paternité (registre confidentiel)
    Route::get('maternite', [DossierMaterniteController::class, 'index'])->name('maternite.index');
    Route::get('maternite/creer', [DossierMaterniteController::class, 'create'])->name('maternite.create');
    Route::post('maternite', [DossierMaterniteController::class, 'store'])->name('maternite.store');
    Route::get('maternite/exporter', [DossierMaterniteController::class, 'exporter'])->name('maternite.exporter');
    Route::get('maternite/{maternite}/certificat', [DossierMaterniteController::class, 'certificat'])->name('maternite.certificat');
    Route::get('maternite/{maternite}/modifier', [DossierMaterniteController::class, 'edit'])->name('maternite.edit');
    Route::put('maternite/{maternite}', [DossierMaterniteController::class, 'update'])->name('maternite.update');
    Route::get('maternite/{maternite}', [DossierMaterniteController::class, 'show'])->name('maternite.show');

    // Planning annuel
    Route::get('planning', [PlanningCongeController::class, 'index'])->name('planning.index');
    Route::get('planning/modele', [PlanningCongeController::class, 'modele'])->name('planning.modele');
    Route::post('planning/apercu', [PlanningCongeController::class, 'apercuImport'])->name('planning.apercu');
    Route::post('planning/importer', [PlanningCongeController::class, 'importer'])->name('planning.importer');

    // ============================================================
    // 2. DEMANDES DE CONGÉ (4.1)
    // ============================================================

    Route::get('/', [DemandeCongeController::class, 'index'])->name('demandes.index');
    Route::get('demandes', [DemandeCongeController::class, 'index'])->name('demandes.index-alias');
    Route::get('demandes/creer', [DemandeCongeController::class, 'create'])->name('demandes.create');
    Route::post('demandes', [DemandeCongeController::class, 'store'])->name('demandes.store');

    // Transitions
    Route::post('demandes/{demande}/soumettre', [DemandeCongeController::class, 'soumettre'])->name('demandes.soumettre');
    Route::post('demandes/{demande}/autoriser', [DemandeCongeController::class, 'autoriser'])->name('demandes.autoriser');
    Route::post('demandes/{demande}/refuser', [DemandeCongeController::class, 'refuser'])->name('demandes.refuser');
    Route::post('demandes/{demande}/programmer', [DemandeCongeController::class, 'programmer'])->name('demandes.programmer');
    Route::post('demandes/{demande}/demarrer', [DemandeCongeController::class, 'demarrer'])->name('demandes.demarrer');
    Route::post('demandes/{demande}/confirmer-reprise', [DemandeCongeController::class, 'confirmerReprise'])->name('demandes.confirmer-reprise');
    Route::post('demandes/{demande}/annuler', [DemandeCongeController::class, 'annuler'])->name('demandes.annuler');
    Route::get('demandes/{demande}/acte', [DemandeCongeController::class, 'imprimerActe'])->name('demandes.acte');

    // CRUD final
    Route::get('demandes/{demande}/modifier', [DemandeCongeController::class, 'edit'])->name('demandes.edit');
    Route::put('demandes/{demande}', [DemandeCongeController::class, 'update'])->name('demandes.update');
    Route::delete('demandes/{demande}', [DemandeCongeController::class, 'destroy'])->name('demandes.destroy');
    Route::get('demandes/{demande}', [DemandeCongeController::class, 'show'])->name('demandes.show');
});