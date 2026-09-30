<?php

use App\Domain\Administration\Models\Entreprise;
use App\Domain\Administration\Models\Utilisateur;

beforeEach(function () {
    $this->seed();
    $this->actingAs(Utilisateur::where('role', Utilisateur::ROLE_SUPER_ADMIN)->first());
});

it('affiche la fiche entreprise', function () {
    $this->get(route('admin.entreprise.index'))->assertOk();
});

it('modifie la fiche entreprise', function () {
    $this->put(route('admin.entreprise.update'), [
        'nom' => 'Nouveau nom SARL',
        'sigle' => 'NN',
        'pays' => 'Togo',
        'devise' => 'XOF',
    ])->assertRedirect();

    $this->assertDatabaseHas('entreprises', ['nom' => 'Nouveau nom SARL']);
});

it('refuse un nom vide', function () {
    $this->put(route('admin.entreprise.update'), ['nom' => ''])
        ->assertSessionHasErrors('nom');
});