<?php

use App\Http\Controllers\Administration\EntrepriseController;
use App\Http\Controllers\Administration\JournalAuditController;
use App\Http\Controllers\Administration\PermissionController;
use App\Http\Controllers\Administration\UtilisateurController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {

    // 8.1 — Entreprise
    Route::get('entreprise', [EntrepriseController::class, 'index'])
        ->middleware('permission:admin.entreprise.view')->name('entreprise.index');
    Route::get('entreprise/edit', [EntrepriseController::class, 'edit'])
        ->middleware('permission:admin.entreprise.manage')->name('entreprise.edit');
    Route::put('entreprise', [EntrepriseController::class, 'update'])
        ->middleware('permission:admin.entreprise.manage')->name('entreprise.update');

    // 8.4 — Utilisateurs
    Route::resource('utilisateurs', UtilisateurController::class)
        ->except(['destroy'])
        ->middleware('permission:admin.utilisateurs.view');
    Route::post('utilisateurs/{utilisateur}/toggle-actif', [UtilisateurController::class, 'toggleActif'])
        ->middleware('permission:admin.utilisateurs.manage')->name('utilisateurs.toggle-actif');
    Route::delete('utilisateurs/{utilisateur}', [UtilisateurController::class, 'destroy'])
        ->middleware('permission:admin.utilisateurs.manage')->name('utilisateurs.destroy');

    // 8.4 — Permissions
    Route::get('permissions/matrice', [PermissionController::class, 'matrice'])
        ->middleware('permission:admin.permissions.view')->name('permissions.matrice');
    Route::put('permissions/matrice', [PermissionController::class, 'modifierMatrice'])
        ->middleware('permission:admin.permissions.manage')->name('permissions.matrice.update');
    Route::get('utilisateurs/{utilisateur}/exceptions', [PermissionController::class, 'exceptions'])
        ->middleware('permission:admin.permissions.view')->name('permissions.exceptions');
    Route::put('utilisateurs/{utilisateur}/exceptions', [PermissionController::class, 'modifierExceptions'])
        ->middleware('permission:admin.permissions.manage')->name('permissions.exceptions.update');

    // 8.5 — Journal d'audit
    Route::get('audit', [JournalAuditController::class, 'index'])
        ->middleware('permission:admin.audit.view')->name('audit.index');
    Route::get('audit/{audit}', [JournalAuditController::class, 'show'])
        ->middleware('permission:admin.audit.view')->name('audit.show');
});