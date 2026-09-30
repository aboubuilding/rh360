<?php

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Organisation\Models\Structure;
use App\Domain\Organisation\Models\TypeStructure;

beforeEach(function () {
    $this->seed();
    $this->actingAs(Utilisateur::where('role', Utilisateur::ROLE_SUPER_ADMIN)->first());
    $this->structure = Structure::create([
        'entreprise_id' => 1, 'type_structure_id' => TypeStructure::first()->id,
        'code' => 'DEP-TEST', 'nom' => 'Structure test', 'actif' => true, 'etat' => 1,
    ]);
});

it('liste les postes', function () {
    $this->get(route('organisation.postes.index'))->assertOk();
});

it('crée un poste', function () {
    $this->post(route('organisation.postes.store'), [
        'structure_id' => $this->structure->id,
        'code' => 'POSTE-TEST',
        'intitule' => 'Poste test',
        'actif' => true,
    ])->assertRedirect();

    $this->assertDatabaseHas('postes', ['code' => 'POSTE-TEST']);
});