<?php

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Carriere\Enums\StatutMouvement;
use App\Domain\Carriere\Enums\TypeMouvement;
use App\Domain\Carriere\Models\MouvementCarriere;
use App\Domain\Organisation\Models\Poste;
use App\Domain\Personnel\Models\Salarie;

beforeEach(function () {
    $this->seed();
    $this->admin = Utilisateur::where('role', Utilisateur::ROLE_SUPER_ADMIN)->first();
    $this->actingAs($this->admin);
    $this->salarie = Salarie::first();
    $this->poste = Poste::first();
});

it('liste les mouvements', function () {
    $this->get(route('carriere.mouvements.index'))
        ->assertOk()
        ->assertSee('Actes de carrière');
});

it('crée un mouvement en brouillon', function () {
    $this->post(route('carriere.mouvements.store'), [
        'salarie_id' => $this->salarie->id,
        'type_mouvement' => TypeMouvement::AFFECTATION->value,
        'date_effet' => now()->addDays(30)->format('Y-m-d'),
        'poste_cible_id' => $this->poste->id,
        'motif' => 'Test de création',
    ])->assertRedirect();

    $this->assertDatabaseHas('mouvements_carriere', [
        'salarie_id' => $this->salarie->id,
        'type_mouvement' => TypeMouvement::AFFECTATION->value,
        'statut' => StatutMouvement::BROUILLON->value,
        'etat' => 1,
    ]);
});

it('génère un numéro unique automatiquement', function () {
    $this->post(route('carriere.mouvements.store'), [
        'salarie_id' => $this->salarie->id,
        'type_mouvement' => TypeMouvement::PROMOTION->value,
    ]);

    $mouvement = MouvementCarriere::where('salarie_id', $this->salarie->id)
        ->orderByDesc('id')
        ->first();
    expect($mouvement->numero_mouvement)->toMatch('/^MOV-\d{4}-\d{5}$/');
});

it('refuse un salarié inexistant', function () {
    $this->post(route('carriere.mouvements.store'), [
        'salarie_id' => 99999,
        'type_mouvement' => TypeMouvement::AFFECTATION->value,
    ])->assertSessionHasErrors('salarie_id');
});