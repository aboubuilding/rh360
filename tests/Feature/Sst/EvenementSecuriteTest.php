<?php

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Personnel\Models\Salarie;
use App\Domain\Sst\Enums\StatutEvenementSecurite;
use App\Domain\Sst\Enums\TypeEvenementSecurite;
use App\Domain\Sst\Models\EvenementSecurite;

beforeEach(function () {
    $this->seed();
    $this->admin = Utilisateur::where('role', Utilisateur::ROLE_SUPER_ADMIN)->first();
    $this->actingAs($this->admin);
    $this->salarie = Salarie::first();
});

it('liste les événements sécurité', function () {
    $this->get(route('sst.evenements.index'))
        ->assertOk()
        ->assertSee('Accidents');
});

it('déclare un événement', function () {
    $this->post(route('sst.evenements.store'), [
        'type_evenement' => TypeEvenementSecurite::INCIDENT->value,
        'date_survenance' => now()->subDay()->format('Y-m-d'),
        'intitule' => 'Incident test',
        'localisation' => 'Atelier',
        'description' => 'Description détaillée de l\'incident',
        'participants' => [$this->salarie->id],
    ])->assertRedirect();

    $this->assertDatabaseHas('evenements_securite', [
        'intitule' => 'Incident test',
        'statut' => StatutEvenementSecurite::DECLARE->value,
        'etat' => 1,
    ]);
});

it('clôture un événement avec synthèse', function () {
    $evenement = EvenementSecurite::factory()->create([
        'entreprise_id' => 1,
        'statut' => StatutEvenementSecurite::DECLARE->value,
    ]);

    $this->post(route('sst.evenements.cloturer', $evenement), [
        'synthese_cloture' => 'Analyse complète et actions correctives prises.',
    ])->assertRedirect();

    $evenement->refresh();
    expect($evenement->statut)->toBe(StatutEvenementSecurite::CLOTURE);
    expect($evenement->date_cloture)->not->toBeNull();
});

it('refuse une clôture sans synthèse', function () {
    $evenement = EvenementSecurite::factory()->create([
        'entreprise_id' => 1,
        'statut' => StatutEvenementSecurite::DECLARE->value,
    ]);

    $this->post(route('sst.evenements.cloturer', $evenement), [])
        ->assertSessionHasErrors('synthese_cloture');
});

it('ajoute une action corrective', function () {
    $evenement = EvenementSecurite::factory()->create(['entreprise_id' => 1]);

    $this->post(route('sst.evenements.actions.store', $evenement), [
        'intitule' => 'Action corrective test',
        'responsable_salarie_id' => $this->salarie->id,
        'date_echeance' => now()->addDays(15)->format('Y-m-d'),
    ])->assertRedirect();

    expect($evenement->actions()->count())->toBeGreaterThan(0);
});