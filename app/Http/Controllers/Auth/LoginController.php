<?php

namespace App\Http\Controllers\Auth;

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Administration\Services\VerificateurMotDePasseLegacy;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function __construct(
        private VerificateurMotDePasseLegacy $verificateur
    ) {}

    public function formulaire()
    {
        return view('auth.login');
    }

    public function connecter(Request $request)
    {
        $donnees = $request->validate([
            'identifiant' => ['required', 'string', 'max:200'],
            'password'    => ['required', 'string'],
        ]);

        // Limitation à 5 tentatives / minute par IP+identifiant
        $cle = 'login:'.$request->ip().':'.$donnees['identifiant'];
        if (RateLimiter::tooManyAttempts($cle, 5)) {
            $secondes = RateLimiter::availableIn($cle);
            return response()->json([
                'success' => false,
                'code' => 'TOO_MANY_ATTEMPTS',
                'message' => "Trop de tentatives. Réessayez dans {$secondes} secondes.",
            ], 429);
        }

        $utilisateur = Utilisateur::withoutGlobalScopes()
            ->where('identifiant', $donnees['identifiant'])
            ->first();

        if (! $utilisateur) {
            RateLimiter::hit($cle, 60);
            return response()->json([
                'success' => false,
                'code' => 'USER_NOT_FOUND',
                'message' => 'Identifiant ou mot de passe incorrect.',
            ], 401);
        }

        if (! $this->verificateur->verifier($utilisateur, $donnees['password'])) {
            RateLimiter::hit($cle, 60);
            return response()->json([
                'success' => false,
                'code' => 'INVALID_PASSWORD',
                'message' => 'Identifiant ou mot de passe incorrect.',
            ], 401);
        }

        if (! $utilisateur->estActif()) {
            return response()->json([
                'success' => false,
                'code' => 'ACCOUNT_INACTIVE',
                'message' => 'Ce compte est désactivé.',
            ], 401);
        }

        RateLimiter::clear($cle);

        auth()->login($utilisateur, $request->boolean('remember'));
        $request->session()->regenerate();

        $utilisateur->forceFill(['derniere_connexion' => now()])->save();

        return response()->json([
            'success' => true,
            'message' => 'Bienvenue '.$utilisateur->nom_complet.' !',
            'redirect' => route('dashboard'),
        ]);
    }

    public function deconnecter(Request $request)
    {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}