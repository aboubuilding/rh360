<?php

use App\Domain\Administration\Models\Utilisateur;

beforeEach(function () {
    $this->seed();
    $this->admin = Utilisateur::where('role', Utilisateur::ROLE_SUPER_ADMIN)->first();
    $this->actingAs($this->admin);
});

it('liste les utilisateurs', function () {
    $this->get(route('admin.utilisateurs.index'))
        ->assertOk()
        ->assertSee('Utilisateurs');
});

it('crée un utilisateur', function () {
    $this->post(route('admin.utilisateurs.store'), [
        'nom_complet' => 'Jean Dupont',
        'identifiant' => 'jdupont',
        'email' => 'jean@test.tg',
        'password' => 'Secret@2026',
        'password_confirmation' => 'Secret@2026',
        'role' => Utilisateur::ROLE_RH,
        'actif' => true,
    ])->assertRedirect();

    $this->assertDatabaseHas('utilisateurs', [
        'identifiant' => 'jdupont',
        'role' => Utilisateur::ROLE_RH,
        'etat' => 1,
    ]);
});

it('refuse un identifiant en doublon', function () {
    $this->post(route('admin.utilisateurs.store'), [
        'nom_complet' => 'Doublon',
        'identifiant' => $this->admin->identifiant,
        'password' => 'Secret@2026',
        'password_confirmation' => 'Secret@2026',
        'role' => Utilisateur::ROLE_RH,
    ])->assertSessionHasErrors('identifiant');
});

it('désactive un utilisateur', function () {
    $cible = Utilisateur::factory()->create(['entreprise_id' => 1, 'role' => Utilisateur::ROLE_RH]);

    $this->post(route('admin.utilisateurs.toggle-actif', $cible))
        ->assertRedirect();

    expect($cible->fresh()->actif)->toBeFalse();
});