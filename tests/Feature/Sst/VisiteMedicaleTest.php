<?php

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Personnel\Models\Salarie;
use App\Domain\Sst\Enums\AptitudeMedicale;
use App\Domain\Sst\Enums\StatutVisiteMedicale;
use App\Domain\Sst\Models\VisiteMedicale;

beforeEach(function () {
    $this->seed();
    $this->admin = Utilisateur::where('role', Utilisateur::ROLE_SUPER_ADMIN)->first();
    $this->actingAs($this->admin);
    $this->salarie = Salarie::first();
});

it('liste les visites médicales', function () {
    $this->get(route('sst.visites.index'))
        ->assertOk()
        ->assertSee('Visites médicales');
});

it('programme une nouvelle visite', function () {
    $this->post(route('sst.visites.store'), [
        'salarie_id' => $this->salarie->id,
        'type_visite' => 'periodique',
        'date_prevue' => now()->addDays(45)->format('Y-m-d'),
        'prestataire' => 'Dr. Test',
    ])->assertRedirect();

    $this->assertDatabaseHas('visites_medicales', [
        'salarie_id' => $this->salarie->id,
        'type_visite' => 'periodique',
        'statut' => StatutVisiteMedicale::PLANIFIEE->value,
        'etat' => 1,
    ]);
});

it('refuse une seconde visite du même type le même jour pour le même salarié', function () {
    $donnees = [
        'salarie_id' => $this->salarie->id,
        'type_visite' => 'reprise',
        'date_prevue' => now()->addDays(50)->format('Y-m-d'),
    ];

    $this->post(route('sst.visites.store'), $donnees)->assertRedirect()->assertSessionHasNoErrors();
    $this->post(route('sst.visites.store'), $donnees)->assertSessionHasErrors('date_prevue');

    expect(VisiteMedicale::where('salarie_id', $this->salarie->id)->where('type_visite', 'reprise')->count())->toBe(1);
})->skip(fn () => config('database.default') === 'sqlite', 'SQLite stocke la date avec l\'heure : la contrainte ne s\'y applique pas');

it('renseigne une visite avec aptitude', function () {
    $visite = VisiteMedicale::factory()->create([
        'entreprise_id' => 1,
        'salarie_id' => $this->salarie->id,
        'statut' => StatutVisiteMedicale::PLANIFIEE->value,
    ]);

    $this->post(route('sst.visites.renseigner', $visite), [
        'date_realisation' => now()->format('Y-m-d'),
        'aptitude' => AptitudeMedicale::APTE->value,
    ])->assertRedirect();

    $visite->refresh();
    expect($visite->statut)->toBe(StatutVisiteMedicale::REALISEE);
    expect($visite->aptitude)->toBe(AptitudeMedicale::APTE);
});

it('refuse l\'accès nominatif sans permission sensible', function () {
    $utilisateur = Utilisateur::create([
        'entreprise_id' => 1,
        'nom_complet' => 'Sans Sensible',
        'identifiant' => 'sans_sensible_' . uniqid(),
        'password' => bcrypt('x'),
        'role' => Utilisateur::ROLE_AUDITEUR,
        'actif' => true,
        'etat' => 1,
    ]);

    $visite = VisiteMedicale::first();

    $this->actingAs($utilisateur)
        ->get(route('sst.visites.show', $visite))
        ->assertForbidden();
});

it('permet l\'accès au reporting agrégé sans permission sensible', function () {
    $utilisateur = Utilisateur::create([
        'entreprise_id' => 1,
        'nom_complet' => 'Direction',
        'identifiant' => 'direction_' . uniqid(),
        'password' => bcrypt('x'),
        'role' => Utilisateur::ROLE_DIRECTION,
        'actif' => true,
        'etat' => 1,
    ]);

    $this->actingAs($utilisateur)
        ->get(route('sst.reporting.index'))
        ->assertOk();
});