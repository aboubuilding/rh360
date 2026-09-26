<?php

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Organisation\Models\Structure;
use App\Domain\Organisation\Models\TypeStructure;

beforeEach(function () {
    $this->seed();
    $this->admin = Utilisateur::where('role', Utilisateur::ROLE_SUPER_ADMIN)->first();
    $this->actingAs($this->admin);
    $this->type = TypeStructure::first();
});

it('liste les structures', function () {
    $this->get(route('organisation.structures.index'))->assertOk();
});

it('crée une structure', function () {
    $this->post(route('organisation.structures.store'), [
        'type_structure_id' => $this->type->id,
        'code' => 'DEP-INFO',
        'nom' => 'Département Informatique',
        'actif' => true,
    ])->assertRedirect();

    $this->assertDatabaseHas('structures', ['code' => 'DEP-INFO']);
});

it('refuse de supprimer une structure avec enfants', function () {
    $parent = Structure::create([
        'entreprise_id' => 1, 'type_structure_id' => $this->type->id,
        'code' => 'P', 'nom' => 'Parent', 'actif' => true, 'etat' => 1,
    ]);
    Structure::create([
        'entreprise_id' => 1, 'type_structure_id' => $this->type->id,
        'parent_id' => $parent->id, 'code' => 'E', 'nom' => 'Enfant',
        'actif' => true, 'etat' => 1,
    ]);

    $this->delete(route('organisation.structures.destroy', $parent))
        ->assertSessionHas('error');
});