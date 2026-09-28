<?php

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Paie\Enums\StatutPeriode;
use App\Domain\Paie\Models\PeriodePaie;
use App\Domain\Paie\Models\SaisiePaie;

beforeEach(function () {
    $this->seed();
    $this->admin = Utilisateur::where('role', Utilisateur::ROLE_SUPER_ADMIN)->first();
    $this->actingAs($this->admin);
});

it('interdit la saisie sur une période figée', function () {
    $periode = PeriodePaie::where('statut', StatutPeriode::VALIDEE->value)->first();

    $this->post(route('paie.periodes.saisir', $periode), [
        'salarie_id' => 1,
        'rubrique_id' => 1,
        'montant' => 50000,
    ])->assertSessionHas('error');
});

it('interdit le recalcul d\'une période figée', function () {
    $periode = PeriodePaie::where('statut', StatutPeriode::VALIDEE->value)->first();

    $this->post(route('paie.periodes.calculer', $periode))
        ->assertSessionHas('error');
});

it('autorise le super admin à rouvrir une période', function () {
    $periode = PeriodePaie::where('statut', StatutPeriode::VALIDEE->value)->first();

    $this->post(route('paie.periodes.reouvrir', $periode), [
        'motif' => 'Correction d\'une erreur de saisie identifiée après validation.',
    ])->assertRedirect();

    expect($periode->fresh()->statut)->toBe(StatutPeriode::CALCULEE);
});

it('refuse la réouverture sans motif', function () {
    $periode = PeriodePaie::where('statut', StatutPeriode::VALIDEE->value)->first();

    $this->post(route('paie.periodes.reouvrir', $periode), [])
        ->assertSessionHasErrors('motif');
});

it('refuse la réouverture par un non super admin', function () {
    $utilisateur = Utilisateur::create([
        'entreprise_id' => 1,
        'nom_complet' => 'RH Test',
        'identifiant' => 'rh_test_' . uniqid(),
        'password' => bcrypt('x'),
        'role' => Utilisateur::ROLE_RH,
        'actif' => true,
        'etat' => 1,
    ]);

    $this->actingAs($utilisateur);

    $periode = PeriodePaie::where('statut', StatutPeriode::VALIDEE->value)->first();

    $this->post(route('paie.periodes.reouvrir', $periode), [
        'motif' => 'Tentative non autorisée',
    ])->assertForbidden();
});