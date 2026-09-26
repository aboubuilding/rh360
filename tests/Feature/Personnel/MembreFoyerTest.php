<?php

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Personnel\Models\Salarie;

beforeEach(function () {
    $this->seed();
    $this->actingAs(Utilisateur::where('role', Utilisateur::ROLE_SUPER_ADMIN)->first());
    $this->salarie = Salarie::create([
        'entreprise_id' => 1,
        'matricule' => 'TEST-001',
        'nom' => 'Test',
        'prenoms' => 'Famille',
        'etat' => 1,
    ]);
});

it('ajoute un membre du foyer', function () {
    $this->post(route('personnel.salaries.foyer.store', $this->salarie), [
        'lien_parente' => 'Enfant',
        'nom' => 'Enfant',
        'prenoms' => 'Petit',
        'date_naissance' => '2020-05-10',
        'est_a_charge' => true,
    ])->assertRedirect();

    expect($this->salarie->membresFoyer()->count())->toBe(1);
});

it('archive un membre', function () {
    $membre = $this->salarie->membresFoyer()->create([
        'lien_parente' => 'Conjoint(e)',
        'nom' => 'Conjoint',
        'prenoms' => 'Test',
        'etat' => 1,
    ]);

    $this->post(route('personnel.salaries.foyer.archiver', [$this->salarie, $membre]))
        ->assertRedirect();

    expect($membre->fresh()->etat)->toBe(0);
});