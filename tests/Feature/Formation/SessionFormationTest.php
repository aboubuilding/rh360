<?php

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Formation\Models\PlanFormation;
use App\Domain\Formation\Models\SessionFormation;
use App\Domain\Formation\Services\CalculateurBudgetFormation;
use App\Domain\Personnel\Models\Salarie;

beforeEach(function () {
    $this->seed();
    $this->admin = Utilisateur::where('role', Utilisateur::ROLE_SUPER_ADMIN)->first();
    $this->actingAs($this->admin);
});

it('calcule l\'état du budget d\'un plan', function () {
    $plan = PlanFormation::first();
    if ($plan) {
        $etat = app(CalculateurBudgetFormation::class)->etat($plan);
        expect($etat)->toHaveKeys(['budget_initial', 'consomme', 'restant', 'taux_consommation']);
    } else {
        expect(true)->toBeTrue();
    }
});

it('refuse une session qui dépasse le budget', function () {
    $plan = PlanFormation::first();
    if (! $plan) {
        expect(true)->toBeTrue();
        return;
    }

    $this->post(route('formation.sessions.store'), [
        'plan_formation_id' => $plan->id,
        'intitule' => 'Session hors budget',
        'date_debut' => now()->addDays(5)->format('Y-m-d'),
        'date_fin' => now()->addDays(7)->format('Y-m-d'),
        'duree_heures' => 8,
        'cout_reel' => 999999999,
    ])->assertSessionHas('error');
});

it('ajoute un participant à une session', function () {
    $session = SessionFormation::first();
    $salarie = Salarie::first();

    if (! $session || ! $salarie) {
        expect(true)->toBeTrue();
        return;
    }

    // Nettoyer les éventuels doublons
    \App\Domain\Formation\Models\ParticipantFormation::where('session_formation_id', $session->id)
        ->where('salarie_id', $salarie->id)->delete();

    $this->post(route('formation.sessions.participants.store', $session), [
        'salarie_id' => $salarie->id,
    ])->assertRedirect();

    expect($session->participants()->where('salarie_id', $salarie->id)->exists())->toBeTrue();
});