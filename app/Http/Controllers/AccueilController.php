<?php

namespace App\Http\Controllers;

/**
 * Page d'accueil publique (landing). Un utilisateur déjà connecté est envoyé
 * directement sur son tableau de bord ; le formulaire de connexion n'est
 * affiché que via le lien « Connexion ».
 */
class AccueilController extends Controller
{
    public function __invoke()
    {
        if (auth()->check()) {
            return redirect()->route('dashboard');
        }

        return view('accueil');
    }
}
