<?php

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/connexion', [LoginController::class, 'formulaire'])->name('login');
    Route::post('/connexion', [LoginController::class, 'connecter'])->name('login.post');
});

Route::middleware('auth')->group(function () {
    Route::post('/deconnexion', [LoginController::class, 'deconnecter'])->name('logout');

    Route::get('/', function () {
        return view('dashboard');
    })->middleware('permission:dashboard.view')->name('dashboard');
});