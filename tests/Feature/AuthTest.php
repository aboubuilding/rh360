<?php

use App\Domain\Administration\Models\Utilisateur;
use Illuminate\Support\Facades\Hash;

it('affiche la page de connexion', function () {
    $this->get('/connexion')->assertOk()->assertSee('EXPERT RH 360');
});

it('connecte un utilisateur valide', function () {
    $user = Utilisateur::first();
    $this->post('/connexion', [
        'identifiant' => $user->identifiant,
        'password' => 'Admin@2026!',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);
});

it('rejette un mot de passe invalide', function () {
    $user = Utilisateur::first();
    $this->post('/connexion', [
        'identifiant' => $user->identifiant,
        'password' => 'mauvais',
    ])->assertSessionHasErrors('identifiant');

    $this->assertGuest();
});

it('déconnecte un utilisateur', function () {
    $user = Utilisateur::first();
    $this->actingAs($user)->post('/deconnexion')->assertRedirect(route('login'));
    $this->assertGuest();
});