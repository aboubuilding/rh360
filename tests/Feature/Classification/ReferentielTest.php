<?php

use App\Domain\Administration\Models\Utilisateur;

beforeEach(function () {
    $this->seed();
    $this->admin = Utilisateur::where('role', Utilisateur::ROLE_SUPER_ADMIN)->first();
    $this->actingAs($this->admin);
});

it('liste les référentiels', function () {
    $this->get(route('classification.referentiels.index'))->assertOk();
});

it('crée un référentiel', function () {
    $this->post(route('classification.referentiels.store'), [
        'code' => 'CONFORMITE-CNSS',
        'nom' => 'Conformité CNSS',
        'type_referentiel' => 'conformity',
        'niveau_source' => 'externe',
        'priorite' => 50,
        'actif' => true,
    ])->assertRedirect();

    $this->assertDatabaseHas('referentiels_classification', ['code' => 'CONFORMITE-CNSS']);
});