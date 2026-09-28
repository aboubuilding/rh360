<?php

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Classification\Models\PositionClassification;
use App\Domain\Contrats\Enums\StatutContrat;
use App\Domain\Contrats\Enums\TypeContrat;
use App\Domain\Contrats\Models\Contrat;
use App\Domain\Organisation\Models\Poste;
use App\Domain\Personnel\Models\Salarie;

beforeEach(function () {
    $this->seed();
    $this->admin = Utilisateur::where('role', Utilisateur::ROLE_SUPER_ADMIN)->first();
    $this->actingAs($this->admin);

    $this->salarie = Salarie::first();
    $this->poste = Poste::first();
    $this->position = PositionClassification::first();
});

it('liste les contrats', function () {
    $this->get(route('contrats.contrats.index'))
        ->assertOk()
        ->assertSee('Contrats');
});

it('crée un contrat en brouillon', function () {
    $this->post(route('contrats.contrats.store'), [
        'salarie_id' => $this->salarie->id,
        'reference' => 'CTR-TEST-0001',
        'type_contrat' => TypeContrat::CDI->value,
        'date_debut' => now()->format('Y-m-d'),
        'poste_id' => $this->poste->id,
        'position_classification_id' => $this->position->id,
    ])->assertRedirect();

    $this->assertDatabaseHas('contrats', [
        'reference' => 'CTR-TEST-0001',
        'statut' => StatutContrat::BROUILLON->value,
        'etat' => 1,
    ]);
});

it('refuse une date de fin antérieure à la date de début', function () {
    $this->post(route('contrats.contrats.store'), [
        'salarie_id' => $this->salarie->id,
        'reference' => 'CTR-TEST-0002',
        'type_contrat' => TypeContrat::CDD->value,
        'date_debut' => '2026-06-01',
        'date_fin' => '2026-05-01',
        'poste_id' => $this->poste->id,
        'position_classification_id' => $this->position->id,
    ])->assertSessionHasErrors('date_fin');
});

it('refuse une référence en doublon dans la même entreprise', function () {
    Contrat::factory()->create([
        'entreprise_id' => 1,
        'salarie_id' => $this->salarie->id,
        'reference' => 'CTR-DOUBLON',
    ]);

    $this->post(route('contrats.contrats.store'), [
        'salarie_id' => $this->salarie->id,
        'reference' => 'CTR-DOUBLON',
        'type_contrat' => TypeContrat::CDI->value,
        'date_debut' => now()->format('Y-m-d'),
        'poste_id' => $this->poste->id,
        'position_classification_id' => $this->position->id,
    ])->assertSessionHasErrors('reference');
});