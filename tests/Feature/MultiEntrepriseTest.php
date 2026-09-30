<?php

/*
| CDC §6 — Cloisonnement par entreprise : toutes les données métier portent un
| entreprise_id et chaque requête est filtrée sur l'entreprise de l'utilisateur
| connecté (scope global BelongsToEntreprise).
*/

use App\Domain\Administration\Models\Entreprise;
use App\Domain\Administration\Models\JournalAudit;
use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Personnel\Models\Salarie;

beforeEach(function () {
    $this->seed();

    $this->entreprise1 = Entreprise::find(1);
    $this->admin1 = Utilisateur::where('identifiant', 'superadmin')->first();

    $this->entreprise2 = Entreprise::create([
        'nom' => 'Autre Société', 'pays' => 'Togo', 'devise' => 'XOF', 'actif' => true, 'etat' => 1,
    ]);
    $this->admin2 = Utilisateur::factory()->role(Utilisateur::ROLE_SUPER_ADMIN)->create([
        'entreprise_id' => $this->entreprise2->id,
    ]);
    $this->salarieAutre = Salarie::factory()->create([
        'entreprise_id' => $this->entreprise2->id,
        'nom' => 'ETRANGER',
        'prenoms' => 'Confidentiel',
        'matricule' => 'EXT-0001',
    ]);
});

it('filtre les requêtes Eloquent sur l\'entreprise de l\'utilisateur connecté', function () {
    $this->actingAs($this->admin1);

    expect(Salarie::pluck('entreprise_id')->unique()->all())->toBe([1]);
    expect(Utilisateur::pluck('entreprise_id')->unique()->all())->toBe([1]);
    expect(Salarie::find($this->salarieAutre->id))->toBeNull();
});

it('applique le même filtre dans l\'autre sens', function () {
    $this->actingAs($this->admin2);

    expect(Salarie::pluck('id')->all())->toBe([$this->salarieAutre->id]);
});

it('n\'affiche pas les salariés d\'une autre entreprise dans l\'annuaire', function () {
    $this->actingAs($this->admin1)
        ->get(route('personnel.salaries.index'))
        ->assertOk()
        ->assertDontSee('ETRANGER');
});

it('refuse l\'accès direct à la fiche d\'un salarié d\'une autre entreprise', function () {
    $this->actingAs($this->admin1)
        ->get(route('personnel.salaries.show', $this->salarieAutre->id))
        ->assertNotFound();
});

it('refuse la modification d\'un salarié d\'une autre entreprise', function () {
    $this->actingAs($this->admin1)
        ->put(route('personnel.salaries.update', $this->salarieAutre->id), [
            'nom' => 'PIRATE', 'prenoms' => 'Test',
        ])
        ->assertNotFound();

    expect($this->salarieAutre->fresh()->nom)->toBe('ETRANGER');
});

it('n\'expose pas les salariés d\'une autre entreprise dans la recherche rapide', function () {
    $this->actingAs($this->admin1)
        ->getJson(route('recherche.salaries', ['q' => 'ETRANGER']))
        ->assertOk()
        ->assertJsonCount(0);
});

it('rattache automatiquement une nouvelle donnée à l\'entreprise de l\'utilisateur', function () {
    $this->actingAs($this->admin2);

    $salarie = Salarie::create(['matricule' => 'AUTO-1', 'nom' => 'Auto', 'prenoms' => 'Rattaché', 'etat' => 1]);

    expect($salarie->entreprise_id)->toBe($this->entreprise2->id);
});

it('trace l\'audit dans l\'entreprise concernée', function () {
    $this->actingAs($this->admin2);
    Utilisateur::factory()->create(['entreprise_id' => $this->entreprise2->id, 'identifiant' => 'audit_e2']);

    $this->actingAs($this->admin1);
    expect(JournalAudit::where('details->identifiant', 'audit_e2')->exists())->toBeFalse();

    $this->actingAs($this->admin2);
    expect(JournalAudit::where('entite', 'Utilisateur')->where('action', 'created')->exists())->toBeTrue();
});
