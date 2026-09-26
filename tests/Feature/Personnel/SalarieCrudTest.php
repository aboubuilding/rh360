<?php

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Personnel\Models\Salarie;

beforeEach(function () {
    $this->seed();
    $this->admin = Utilisateur::where('role', Utilisateur::ROLE_SUPER_ADMIN)->first();
    $this->actingAs($this->admin);
});

it('liste les salariés', function () {
    $this->get(route('personnel.salaries.index'))->assertOk();
});

it('crée un salarié', function () {
    $this->post(route('personnel.salaries.store'), [
        'nom' => 'Dupont',
        'prenoms' => 'Jean',
        'sexe' => 'M',
        'date_naissance' => '1990-01-15',
        'telephone_principal' => '90000000',
        'date_embauche' => '2026-01-01',
    ])->assertRedirect();

    $this->assertDatabaseHas('salaries', [
        'nom' => 'Dupont',
        'prenoms' => 'Jean',
        'etat' => 1,
    ]);
});

it('génère un matricule automatiquement', function () {
    $this->post(route('personnel.salaries.store'), [
        'nom' => 'Test',
        'prenoms' => 'Auto',
        'date_embauche' => '2026-01-01',
    ]);

    $salarie = Salarie::where('nom', 'Test')->first();
    expect($salarie->matricule)->toMatch('/^MAT-\d{4}-\d{4}$/');
});

it('marque le dossier comme incomplet si champs manquants', function () {
    $this->post(route('personnel.salaries.store'), [
        'nom' => 'Test',
        'prenoms' => 'Incomplet',
    ]);

    $salarie = Salarie::where('nom', 'Test')->first();
    expect($salarie->statut_dossier)->toBe(\App\Domain\Personnel\Enums\StatutDossier::INCOMPLET);
});

it('refuse un nom vide', function () {
    $this->post(route('personnel.salaries.store'), ['prenoms' => 'Jean'])
        ->assertSessionHasErrors('nom');
});