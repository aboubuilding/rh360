<?php

use App\Domain\Administration\Models\Utilisateur;
use Illuminate\Support\Facades\RateLimiter;

beforeEach(function () {
    $this->seed();
    $this->user = Utilisateur::where('identifiant', 'superadmin')->first();
});

it('affiche la page de connexion', function () {
    $this->get('/connexion')->assertOk()->assertSee('EXPERT RH 360');
});

it('affiche la page d\'accueil publique à un invité, avec un lien vers la connexion', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Pilotez vos équipes')
        ->assertSee(route('login'), false)
        ->assertDontSee('name="password"', false);
});

it('envoie un utilisateur connecté de l\'accueil vers son tableau de bord', function () {
    $this->actingAs($this->user)->get('/')->assertRedirect(route('dashboard'));
});

it('redirige un invité vers la connexion', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

it('connecte un utilisateur valide et renvoie la redirection', function () {
    $this->postJson('/connexion', [
        'identifiant' => 'superadmin',
        'password' => 'Admin@2026!',
    ])->assertOk()->assertJson([
        'success' => true,
        'redirect' => route('dashboard'),
    ]);

    $this->assertAuthenticatedAs($this->user);
});

it('ouvre les pages avec l\'utilisateur relu depuis la session (sans actingAs)', function () {
    $this->postJson('/connexion', ['identifiant' => 'superadmin', 'password' => 'Admin@2026!'])->assertOk();

    // Oublie l'utilisateur en mémoire : la requête suivante doit le relire depuis la session,
    // sous le scope global d'entreprise du modèle Utilisateur (régression : récursion infinie).
    auth()->forgetGuards();

    $this->get(route('dashboard'))->assertOk()->assertSee('Tableau de bord');
    $this->get(route('personnel.salaries.index'))->assertOk();
});

it('enregistre la date de dernière connexion', function () {
    $this->postJson('/connexion', ['identifiant' => 'superadmin', 'password' => 'Admin@2026!']);

    expect($this->user->fresh()->derniere_connexion)->not->toBeNull();
});

it('rejette un mot de passe invalide', function () {
    $this->postJson('/connexion', [
        'identifiant' => 'superadmin',
        'password' => 'mauvais',
    ])->assertStatus(401)->assertJson(['success' => false, 'code' => 'INVALID_CREDENTIALS']);

    $this->assertGuest();
});

it('rejette un identifiant inconnu avec le même message qu\'un mauvais mot de passe', function () {
    $inconnu = $this->postJson('/connexion', ['identifiant' => 'fantome', 'password' => 'x'])
        ->assertStatus(401)->json();
    $mauvais = $this->postJson('/connexion', ['identifiant' => 'superadmin', 'password' => 'x'])
        ->assertStatus(401)->json();

    // Pas d'énumération des comptes : code et message identiques
    expect($inconnu)->toBe($mauvais);
    $this->assertGuest();
});

it('refuse un compte désactivé', function () {
    $this->user->update(['actif' => false]);

    $this->postJson('/connexion', ['identifiant' => 'superadmin', 'password' => 'Admin@2026!'])
        ->assertStatus(401)->assertJson(['code' => 'ACCOUNT_INACTIVE']);

    $this->assertGuest();
});

it('exige l\'identifiant et le mot de passe', function () {
    $this->postJson('/connexion', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['identifiant', 'password']);
});

it('bloque après 5 tentatives échouées', function () {
    RateLimiter::clear('login:127.0.0.1:superadmin');

    foreach (range(1, 5) as $i) {
        $this->postJson('/connexion', ['identifiant' => 'superadmin', 'password' => 'mauvais'])
            ->assertStatus(401);
    }

    // Même le bon mot de passe est refusé tant que la limite est atteinte
    $this->postJson('/connexion', ['identifiant' => 'superadmin', 'password' => 'Admin@2026!'])
        ->assertStatus(429)->assertJson(['code' => 'TOO_MANY_ATTEMPTS']);

    $this->assertGuest();
});

it('déconnecte un utilisateur', function () {
    $this->actingAs($this->user)->post('/deconnexion')->assertRedirect(route('login'));
    $this->assertGuest();
});
