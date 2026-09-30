<?php

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Contrats\Enums\StatutContrat;
use App\Domain\Contrats\Models\Contrat;
use App\Domain\Contrats\Models\HistoriqueContrat;

beforeEach(function () {
    $this->seed();
    $this->admin = Utilisateur::where('role', Utilisateur::ROLE_SUPER_ADMIN)->first();
    $this->actingAs($this->admin);
    $this->contrat = Contrat::first();
});

it('soumet un contrat brouillon', function () {
    $this->contrat->update(['statut' => StatutContrat::BROUILLON->value]);

    $this->post(route('contrats.contrats.soumettre', $this->contrat))
        ->assertRedirect();

    expect($this->contrat->fresh()->statut)->toBe(StatutContrat::SOUMIS);
});

it('valide un contrat soumis', function () {
    $this->contrat->update(['statut' => StatutContrat::SOUMIS->value]);

    $this->post(route('contrats.contrats.valider', $this->contrat), [
        'note_derogation' => null,
    ])->assertRedirect();

    expect($this->contrat->fresh()->statut)->toBe(StatutContrat::VALIDE);
});

it('refuse la validation d\'un contrat en brouillon', function () {
    $this->contrat->update(['statut' => StatutContrat::BROUILLON->value]);

    $this->post(route('contrats.contrats.valider', $this->contrat))
        ->assertForbidden();
});

it('retourne un contrat au brouillon avec motif obligatoire', function () {
    $this->contrat->update(['statut' => StatutContrat::SOUMIS->value]);

    // Sans motif → erreur
    $this->post(route('contrats.contrats.retourner-brouillon', $this->contrat), [])
        ->assertSessionHasErrors('motif');

    // Avec motif → OK
    $this->post(route('contrats.contrats.retourner-brouillon', $this->contrat), [
        'motif' => 'Informations incomplètes',
    ])->assertRedirect();

    expect($this->contrat->fresh()->statut)->toBe(StatutContrat::BROUILLON);
});

it('annule un contrat avec motif obligatoire', function () {
    $this->contrat->update(['statut' => StatutContrat::SOUMIS->value]);

    $this->post(route('contrats.contrats.annuler', $this->contrat), [
        'motif' => 'Erreur de saisie',
    ])->assertRedirect();

    expect($this->contrat->fresh()->statut)->toBe(StatutContrat::ANNULE);
});

it('enregistre chaque transition dans l\'historique', function () {
    $this->contrat->update(['statut' => StatutContrat::BROUILLON->value]);
    $avant = HistoriqueContrat::where('contrat_id', $this->contrat->id)->count();

    $this->post(route('contrats.contrats.soumettre', $this->contrat));

    $apres = HistoriqueContrat::where('contrat_id', $this->contrat->id)->count();
    expect($apres)->toBeGreaterThan($avant);

    $derniere = HistoriqueContrat::where('contrat_id', $this->contrat->id)->latest('id')->first();
    expect($derniere->instantane)->toMatchArray([
        'statut_avant' => StatutContrat::BROUILLON->value,
        'statut_apres' => StatutContrat::SOUMIS->value,
    ]);
});

it('conserve le motif du retour au brouillon dans l\'historique', function () {
    $this->contrat->update(['statut' => StatutContrat::SOUMIS->value]);

    $this->post(route('contrats.contrats.retourner-brouillon', $this->contrat), [
        'motif' => 'Classification à revoir',
    ]);

    $derniere = HistoriqueContrat::where('contrat_id', $this->contrat->id)->latest('id')->first();
    expect($derniere->instantane['motif'])->toBe('Classification à revoir');
});

it('refuse l\'annulation sans motif', function () {
    $this->contrat->update(['statut' => StatutContrat::SOUMIS->value]);

    $this->post(route('contrats.contrats.annuler', $this->contrat), [])
        ->assertSessionHasErrors('motif');

    expect($this->contrat->fresh()->statut)->toBe(StatutContrat::SOUMIS);
});

it('référence la signature d\'un contrat validé', function () {
    $this->contrat->update(['statut' => StatutContrat::VALIDE->value]);

    $this->post(route('contrats.contrats.signer', $this->contrat), [
        'date_signature' => now()->format('Y-m-d'),
        'reference_signee' => 'SIG-2026-001',
    ])->assertRedirect();

    $contrat = $this->contrat->fresh();
    expect($contrat->statut)->toBe(StatutContrat::SIGNE);
    expect($contrat->reference_signee)->toBe('SIG-2026-001');
    expect($contrat->date_signature)->not->toBeNull();
});

it('exige la date et la référence de signature', function () {
    $this->contrat->update(['statut' => StatutContrat::VALIDE->value]);

    $this->post(route('contrats.contrats.signer', $this->contrat), [])
        ->assertSessionHasErrors(['date_signature', 'reference_signee']);
});

it('refuse de signer un contrat non validé', function () {
    $this->contrat->update(['statut' => StatutContrat::SOUMIS->value]);

    $this->post(route('contrats.contrats.signer', $this->contrat), [
        'date_signature' => now()->format('Y-m-d'),
        'reference_signee' => 'SIG-2026-002',
    ])->assertForbidden();
});

describe('contrat signé', function () {
    beforeEach(function () {
        $this->contrat->update(['statut' => StatutContrat::SIGNE->value]);
    });

    it('n\'est plus modifiable', function () {
        $this->put(route('contrats.contrats.update', $this->contrat), [
            'reference' => 'MODIF-APRES-SIGNATURE',
            'type_contrat' => $this->contrat->type_contrat,
            'date_debut' => now()->format('Y-m-d'),
            'poste_id' => $this->contrat->poste_id,
            'position_classification_id' => $this->contrat->position_classification_id,
        ])->assertForbidden();

        expect($this->contrat->fresh()->reference)->not->toBe('MODIF-APRES-SIGNATURE');
    });

    it('ne peut plus être annulé ni retourné au brouillon', function () {
        $this->post(route('contrats.contrats.annuler', $this->contrat), ['motif' => 'Tentative'])
            ->assertForbidden();
        $this->post(route('contrats.contrats.retourner-brouillon', $this->contrat), ['motif' => 'Tentative'])
            ->assertForbidden();

        expect($this->contrat->fresh()->statut)->toBe(StatutContrat::SIGNE);
    });

    it('évolue par avenant rattaché au contrat d\'origine', function () {
        $this->post(route('contrats.contrats.store-avenant', $this->contrat), [
            'reference' => 'AVN-2026-0001',
            'type_contrat' => $this->contrat->type_contrat,
            'date_debut' => now()->addMonth()->format('Y-m-d'),
            'poste_id' => $this->contrat->poste_id,
            'position_classification_id' => $this->contrat->position_classification_id,
        ])->assertRedirect();

        $avenant = Contrat::where('reference', 'AVN-2026-0001')->first();
        expect($avenant)->not->toBeNull();
        expect($avenant->parent_id)->toBe($this->contrat->id);
        expect($avenant->salarie_id)->toBe($this->contrat->salarie_id);
        expect($avenant->statut)->toBe(StatutContrat::BROUILLON);
        expect($avenant->estAvenant())->toBeTrue();
    });
});

it('refuse un avenant sur un contrat non signé', function () {
    $this->contrat->update(['statut' => StatutContrat::VALIDE->value]);

    $this->post(route('contrats.contrats.store-avenant', $this->contrat), [
        'reference' => 'AVN-INTERDIT',
        'type_contrat' => $this->contrat->type_contrat,
        'date_debut' => now()->format('Y-m-d'),
        'poste_id' => $this->contrat->poste_id,
        'position_classification_id' => $this->contrat->position_classification_id,
    ])->assertForbidden();

    expect(Contrat::where('reference', 'AVN-INTERDIT')->exists())->toBeFalse();
});

it('le Responsable RH prépare mais ne valide pas', function () {
    $rh = Utilisateur::factory()->role(Utilisateur::ROLE_RH)->create();
    $this->contrat->update(['statut' => StatutContrat::BROUILLON->value]);

    $this->actingAs($rh)->post(route('contrats.contrats.soumettre', $this->contrat))->assertRedirect();
    $this->actingAs($rh)->post(route('contrats.contrats.valider', $this->contrat))->assertForbidden();

    expect($this->contrat->fresh()->statut)->toBe(StatutContrat::SOUMIS);
});

it('le Responsable RH référence la signature d\'un contrat validé', function () {
    $rh = Utilisateur::factory()->role(Utilisateur::ROLE_RH)->create();
    $this->contrat->update(['statut' => StatutContrat::VALIDE->value]);

    $this->actingAs($rh)->post(route('contrats.contrats.signer', $this->contrat), [
        'date_signature' => now()->format('Y-m-d'),
        'reference_signee' => 'SIG-RH-001',
    ])->assertRedirect();

    expect($this->contrat->fresh()->statut)->toBe(StatutContrat::SIGNE);
});

it('l\'Auditeur consulte les contrats sans pouvoir agir', function () {
    $auditeur = Utilisateur::factory()->role(Utilisateur::ROLE_AUDITEUR)->create();
    $this->contrat->update(['statut' => StatutContrat::BROUILLON->value]);

    $this->actingAs($auditeur)->get(route('contrats.contrats.index'))->assertOk();
    $this->actingAs($auditeur)->post(route('contrats.contrats.soumettre', $this->contrat))->assertForbidden();
});

it('bloque la validation hors plafond sans dérogation documentée')
    ->todo('CDC §2.3/§2.4 : contrôle automatique des plafonds (regles_contrats) non implémenté dans ValiderContrat');