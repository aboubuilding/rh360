<?php

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Conges\Enums\StatutDemandeConge;
use App\Domain\Conges\Models\DemandeConge;
use App\Domain\Conges\Models\TypeConge;
use App\Domain\Personnel\Models\Salarie;

beforeEach(function () {
    $this->seed();
    $this->admin = Utilisateur::where('role', Utilisateur::ROLE_SUPER_ADMIN)->first();
    $this->actingAs($this->admin);
    $this->salarie = Salarie::first();
    $this->typeConge = TypeConge::where('code', 'CA')->first();
});

it('liste les demandes', function () {
    $this->get(route('conges.demandes.index'))
        ->assertOk()
        ->assertSee('Demandes de congés');
});

it('crée une demande de congé', function () {
    $this->post(route('conges.demandes.store'), [
        'salarie_id' => $this->salarie->id,
        'type_conge_id' => $this->typeConge->id,
        'date_debut' => now()->addDays(15)->format('Y-m-d'),
        'date_reprise' => now()->addDays(29)->format('Y-m-d'),
        'motif' => 'Congé annuel',
    ])->assertRedirect();

    $this->assertDatabaseHas('demandes_conges', [
        'salarie_id' => $this->salarie->id,
        'statut' => StatutDemandeConge::BROUILLON->value,
        'etat' => 1,
    ]);
});

it('génère un numéro unique', function () {
    $this->post(route('conges.demandes.store'), [
        'salarie_id' => $this->salarie->id,
        'type_conge_id' => $this->typeConge->id,
        'date_debut' => now()->addDays(15)->format('Y-m-d'),
    ]);

    $demande = DemandeConge::where('salarie_id', $this->salarie->id)->latest()->first();
    expect($demande->numero_demande)->toMatch('/^DEM-\d{4}-\d{5}$/');
});

it('refuse une date de reprise antérieure à la date de début', function () {
    $this->post(route('conges.demandes.store'), [
        'salarie_id' => $this->salarie->id,
        'type_conge_id' => $this->typeConge->id,
        'date_debut' => '2026-06-15',
        'date_reprise' => '2026-06-10',
    ])->assertSessionHasErrors('date_reprise');
});

it('calcule automatiquement la durée en jours calendaires', function () {
    $this->post(route('conges.demandes.store'), [
        'salarie_id' => $this->salarie->id,
        'type_conge_id' => $this->typeConge->id,
        'date_debut' => '2026-06-01',
        'date_reprise' => '2026-06-15',
    ]);

    $demande = DemandeConge::where('salarie_id', $this->salarie->id)->latest()->first();
    expect((float) $demande->duree_jours)->toBe(14.0);
});