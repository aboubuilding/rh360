<?php

use App\Http\Controllers\AccueilController;
use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

// Page d'accueil publique ; redirige vers le tableau de bord si l'utilisateur est connecté
Route::get('/', AccueilController::class)->name('accueil');

Route::middleware('guest')->group(function () {
    Route::get('/connexion', [LoginController::class, 'formulaire'])->name('login');
    Route::post('/connexion', [LoginController::class, 'connecter'])->name('login.post');
});

Route::middleware('auth')->group(function () {
    Route::post('/deconnexion', [LoginController::class, 'deconnecter'])->name('logout');
});

// Le tableau de bord (route « dashboard ») est défini dans routes/web/pilotage.php.