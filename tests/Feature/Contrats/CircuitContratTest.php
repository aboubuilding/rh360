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
});