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

it('vérifie un mouvement à vérifier (étape DRH avant validation)', function () {
    $m = MouvementCarriere::factory()->create([
        'entreprise_id' => 1,
        'statut' => StatutMouvement::A_VERIFIER->value,
    ]);

    $this->post(route('carriere.mouvements.verifier', $m))->assertRedirect();

    expect($m->fresh()->statut)->toBe(StatutMouvement::VERIFIE);
});

it('refuse de valider un mouvement qui n\'a pas été vérifié', function () {
    $m = MouvementCarriere::factory()->create([
        'entreprise_id' => 1,
        'statut' => StatutMouvement::A_VERIFIER->value,
    ]);

    $this->post(route('carriere.mouvements.valider', $m))->assertForbidden();

    expect($m->fresh()->statut)->toBe(StatutMouvement::A_VERIFIER);
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

it('applique immédiatement un acte validé à effet rétroactif', function () {
    $salarie = \App\Domain\Personnel\Models\Salarie::first();
    $poste = \App\Domain\Organisation\Models\Poste::first();
    $m = MouvementCarriere::factory()->create([
        'entreprise_id' => 1,
        'salarie_id' => $salarie->id,
        'statut' => StatutMouvement::VALIDE->value,
        'poste_cible_id' => $poste->id,
        'structure_cible_id' => $poste->structure_id,
    ]);

    $this->post(route('carriere.mouvements.programmer', $m), [
        'date_effet' => now()->subMonths(2)->format('Y-m-d'),
    ])->assertRedirect();

    $m->refresh();
    expect($m->statut)->toBe(StatutMouvement::EFFECTIF);
    expect($m->date_effet->format('Y-m-d'))->toBe(now()->subMonths(2)->format('Y-m-d'));

    $affectation = \App\Domain\Personnel\Models\Affectation::where('salarie_id', $salarie->id)
        ->where('en_cours', true)->sole();
    expect($affectation->poste_id)->toBe($poste->id);
    expect($affectation->date_debut->format('Y-m-d'))->toBe($m->date_effet->format('Y-m-d'));

    // L'historique garde la trace du passage par « programmé »
    expect(\App\Domain\Carriere\Models\InstantaneCarriere::where('mouvement_id', $m->id)->exists())->toBeTrue();
});

it('exige une date d\'effet pour programmer', function () {
    $m = MouvementCarriere::factory()->create([
        'entreprise_id' => 1,
        'statut' => StatutMouvement::VALIDE->value,
    ]);

    $this->post(route('carriere.mouvements.programmer', $m), [])->assertSessionHasErrors('date_effet');
    expect($m->fresh()->statut)->toBe(StatutMouvement::VALIDE);
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