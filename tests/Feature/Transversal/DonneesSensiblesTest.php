<?php

/*
| CDC §6 — Données sensibles : social et santé (CNSS, AMU, assurance), bancaire,
| coordonnées GPS. Sans la permission dédiée, ces champs ne sont ni affichés,
| ni modifiables, ni exportés.
*/

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Personnel\Models\Salarie;

beforeEach(function () {
    $this->seed();
    $this->salarie = Salarie::where('matricule', 'MAT-2026-0001')->first();
    $this->salarie->update(['gps_latitude' => 6.1319, 'gps_longitude' => 1.2228]);

    // Direction : consulte les dossiers salariés, sans aucune permission sensible
    $this->direction = Utilisateur::factory()->role(Utilisateur::ROLE_DIRECTION)->create();
    // Responsable RH : gère les dossiers, a le social/santé mais ni bancaire ni GPS
    $this->rh = Utilisateur::factory()->role(Utilisateur::ROLE_RH)->create();
    $this->superAdmin = Utilisateur::where('identifiant', 'superadmin')->first();
});

it('liste les champs protégés par famille', function () {
    expect(Salarie::CHAMPS_SENSIBLES)->toHaveKeys(['sensitive.social_health', 'sensitive.banking', 'sensitive.gps']);
    expect(Salarie::champsSensiblesInterdits($this->superAdmin))->toBe([]);
    expect(Salarie::champsSensiblesInterdits($this->rh))
        ->toContain('compte_bancaire', 'gps_latitude')
        ->not->toContain('numero_cnss');
});

it('masque les données bancaires et sociales sans permission', function () {
    $this->actingAs($this->direction)
        ->get(route('personnel.salaries.show', $this->salarie))
        ->assertOk()
        ->assertDontSee($this->salarie->compte_bancaire)
        ->assertDontSee($this->salarie->numero_cnss);
});

it('affiche les données sensibles avec les permissions', function () {
    $this->actingAs($this->superAdmin)
        ->get(route('personnel.salaries.show', $this->salarie))
        ->assertOk()
        ->assertSee($this->salarie->compte_bancaire)
        ->assertSee($this->salarie->numero_cnss);
});

it('ignore la modification des champs bancaires et GPS envoyés sans permission', function () {
    $ribAvant = $this->salarie->compte_bancaire;

    $this->actingAs($this->rh)
        ->put(route('personnel.salaries.update', $this->salarie), [
            'nom' => $this->salarie->nom,
            'prenoms' => $this->salarie->prenoms,
            'compte_bancaire' => 'RIB-FRAUDULEUX',
            'gps_latitude' => 0,
            'numero_cnss' => 'CNSS-NOUVEAU', // autorisé pour le RH (sensitive.social_health)
        ])
        ->assertRedirect();

    $s = $this->salarie->fresh();
    expect($s->compte_bancaire)->toBe($ribAvant);
    expect((float) $s->gps_latitude)->toBe(6.1319);
    expect($s->numero_cnss)->toBe('CNSS-NOUVEAU');
});

it('enregistre les champs sensibles quand l\'utilisateur a la permission', function () {
    $this->actingAs($this->superAdmin)
        ->put(route('personnel.salaries.update', $this->salarie), [
            'nom' => $this->salarie->nom,
            'prenoms' => $this->salarie->prenoms,
            'compte_bancaire' => 'TG53 9999',
        ])
        ->assertRedirect();

    expect($this->salarie->fresh()->compte_bancaire)->toBe('TG53 9999');
});

it('ignore les champs sensibles à la création sans permission', function () {
    $this->actingAs($this->rh)
        ->post(route('personnel.salaries.store'), [
            'nom' => 'Sensible',
            'prenoms' => 'Creation',
            'compte_bancaire' => 'RIB-INTERDIT',
        ])
        ->assertRedirect();

    expect(Salarie::where('nom', 'Sensible')->first()->compte_bancaire)->toBeNull();
});

it('réserve le registre maternité à sensitive.social_health', function () {
    $this->actingAs($this->direction)->get(route('conges.maternite.index'))->assertForbidden();
    $this->actingAs($this->rh)->get(route('conges.maternite.index'))->assertOk();
});

it('exclut les données sensibles des exports sans permission')
    ->todo('CDC §2.1 / §6 : l\'export Excel de l\'annuaire des salariés n\'est pas implémenté');
