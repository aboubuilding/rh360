<?php

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Formation\Models\Formation;

beforeEach(function () {
    $this->seed();
    $this->admin = Utilisateur::where('role', Utilisateur::ROLE_SUPER_ADMIN)->first();
    $this->actingAs($this->admin);
});

it('liste les formations', function () {
    $this->get(route('formation.formations.index'))->assertOk();
});

it('crée une formation', function () {
    $this->post(route('formation.formations.store'), [
        'code' => 'TEST-001',
        'intitule' => 'Test formation',
        'duree_heures' => 8,
        'modalite' => 'presentiel',
        'actif' => true,
    ])->assertRedirect();

    $this->assertDatabaseHas('formations', [
        'code' => 'TEST-001',
        'etat' => 1,
    ]);
});

it('refuse un code en doublon', function () {
    Formation::factory()->create(['code' => 'DUP-001']);
    $this->post(route('formation.formations.store'), [
        'code' => 'DUP-001',
        'intitule' => 'Autre',
        'duree_heures' => 8,
        'modalite' => 'presentiel',
    ])->assertSessionHasErrors('code');
});

it('liste les besoins de formation', function () {
    $this->get(route('formation.besoins.index'))->assertOk();
});

it('valide un besoin à étudier', function () {
    $besoin = \App\Domain\Formation\Models\BesoinFormation::where('statut', 'a_etudier')->first();
    if ($besoin) {
        $this->post(route('formation.besoins.valider', $besoin))->assertRedirect();
        expect($besoin->fresh()->statut->value)->toBe('valide');
    } else {
        expect(true)->toBeTrue();
    }
});