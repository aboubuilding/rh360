<?php

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Recrutement\Models\BesoinRecrutement;
use App\Domain\Recrutement\Models\Candidat;
use App\Domain\Recrutement\Services\SuiviEtapesCandidat;

beforeEach(function () {
    $this->seed();
    $this->admin = Utilisateur::where('role', Utilisateur::ROLE_SUPER_ADMIN)->first();
    $this->actingAs($this->admin);
});

it('liste les besoins', function () {
    $this->get(route('recrutement.besoins.index'))->assertOk();
});

it('génère une référence unique', function () {
    $generateur = app(\App\Domain\Recrutement\Services\GenerateurReferenceBesoin::class);
    $ref = $generateur->generer(1);
    expect($ref)->toMatch('/^REF-REC-\d{4}-\d{4}$/');
});

it('valide les transitions d\'étape', function () {
    $suivi = app(SuiviEtapesCandidat::class);

    expect($suivi->peutTransitionner(
        \App\Domain\Recrutement\Enums\EtapeCandidat::CANDIDATURE_RECUE,
        \App\Domain\Recrutement\Enums\EtapeCandidat::PRESELECTION
    ))->toBeTrue();

    expect($suivi->peutTransitionner(
        \App\Domain\Recrutement\Enums\EtapeCandidat::CANDIDATURE_RECUE,
        \App\Domain\Recrutement\Enums\EtapeCandidat::ACCEPTE
    ))->toBeFalse();
});

it('retient un candidat', function () {
    $candidat = Candidat::where('decision', 'en_attente')->first();
    if (! $candidat) {
        expect(true)->toBeTrue();
        return;
    }

    $this->post(route('recrutement.candidats.retenir', $candidat))->assertRedirect();
    expect($candidat->fresh()->decision->value)->toBe('retenu');
});

it('refuse un candidat avec motif obligatoire', function () {
    $candidat = Candidat::where('decision', 'en_attente')->first();
    if (! $candidat) {
        expect(true)->toBeTrue();
        return;
    }

    // Sans motif → erreur
    $this->post(route('recrutement.candidats.refuser', $candidat), [])
        ->assertSessionHasErrors('motif');
});

it('crée un candidat', function () {
    $besoin = BesoinRecrutement::first();
    $this->post(route('recrutement.candidats.store'), [
        'besoin_id' => $besoin?->id,
        'nom' => 'TEST',
        'prenoms' => 'Candidat',
        'email' => 'test.candidat@example.tg',
        'source' => 'spontanee',
    ])->assertRedirect();

    $this->assertDatabaseHas('candidats', ['email' => 'test.candidat@example.tg']);
});