<?php

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Carriere\Enums\StatutMouvement;
use App\Domain\Carriere\Enums\TypeMouvement;
use App\Domain\Carriere\Models\MouvementCarriere;

beforeEach(function () {
    $this->seed();
    $this->admin = Utilisateur::where('role', Utilisateur::ROLE_SUPER_ADMIN)->first();
    $this->actingAs($this->admin);
});

it('soumet un mouvement brouillon', function () {
    $m = MouvementCarriere::factory()->create([
        'entreprise_id' => 1,
        'statut' => StatutMouvement::BROUILLON->value,
        'cree_par' => $this->admin->id,
    ]);

    $this->post(route('carriere.mouvements.soumettre', $m))->assertRedirect();
    expect($m->fresh()->statut)->toBe(StatutMouvement::PROPOSE);
});

it('contrôle un mouvement proposé', function () {
    $m = MouvementCarriere::factory()->create([
        'entreprise_id' => 1,
        'statut' => StatutMouvement::PROPOSE->value,
    ]);

    $this->post(route('carriere.mouvements.controler', $m), [
        'observations' => 'Contrôle OK',
    ])->assertRedirect();

    expect($m->fresh()->statut)->toBe(StatutMouvement::A_VERIFIER);
});

it('valide un mouvement vérifié', function () {
    $m = MouvementCarriere::factory()->create([
        'entreprise_id' => 1,
        'statut' => StatutMouvement::VERIFIE->value,
    ]);

    $this->post(route('carriere.mouvements.valider', $m), [
        'note_validation' => 'Validé par le DRH',
    ])->assertRedirect();

    expect($m->fresh()->statut)->toBe(StatutMouvement::VALIDE);
});

it('rejette un mouvement en circuit', function () {
    $m = MouvementCarriere::factory()->create([
        'entreprise_id' => 1,
        'statut' => StatutMouvement::A_VERIFIER->value,
    ]);

    $this->post(route('carriere.mouvements.rejeter', $m), [
        'motif' => 'Dossier incomplet',
    ])->assertRedirect();

    expect($m->fresh()->statut)->toBe(StatutMouvement::REJETE);
});

it('refuse de rejeter sans motif', function () {
    $m = MouvementCarriere::factory()->create([
        'entreprise_id' => 1,
        'statut' => StatutMouvement::A_VERIFIER->value,
    ]);

    $this->post(route('carriere.mouvements.rejeter', $m), [])
        ->assertSessionHasErrors('motif');
});

it('programme un mouvement validé avec date future', function () {
    $m = MouvementCarriere::factory()->create([
        'entreprise_id' => 1,
        'statut' => StatutMouvement::VALIDE->value,
    ]);

    $this->post(route('carriere.mouvements.programmer', $m), [
        'date_effet' => now()->addDays(30)->format('Y-m-d'),
    ])->assertRedirect();

    expect($m->fresh()->statut)->toBe(StatutMouvement::PROGRAMME);
});

it('refuse de programmer avec date passée', function () {
    $m = MouvementCarriere::factory()->create([
        'entreprise_id' => 1,
        'statut' => StatutMouvement::VALIDE->value,
    ]);

    $this->post(route('carriere.mouvements.programmer', $m), [
        'date_effet' => now()->subDays(1)->format('Y-m-d'),
    ])->assertSessionHasErrors('date_effet');
});

it('annule un mouvement avec motif obligatoire', function () {
    $m = MouvementCarriere::factory()->create([
        'entreprise_id' => 1,
        'statut' => StatutMouvement::VALIDE->value,
    ]);

    $this->post(route('carriere.mouvements.annuler', $m), [
        'motif' => 'Erreur de saisie',
    ])->assertRedirect();

    expect($m->fresh()->statut)->toBe(StatutMouvement::ANNULE);
});