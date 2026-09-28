<?php

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Contrats\Enums\NatureEvenementEssai;
use App\Domain\Contrats\Enums\StatutContrat;
use App\Domain\Contrats\Enums\StatutEvenementEssai;
use App\Domain\Contrats\Models\Contrat;
use App\Domain\Contrats\Models\EvenementEssai;

beforeEach(function () {
    $this->seed();
    $this->admin = Utilisateur::where('role', Utilisateur::ROLE_SUPER_ADMIN)->first();
    $this->actingAs($this->admin);
    $this->contrat = Contrat::first();
    $this->contrat->update(['statut' => StatutContrat::SIGNE->value]);
});

it('déclare un événement d\'essai', function () {
    $this->post(route('contrats.contrats.evenements-essai.store', $this->contrat), [
        'nature' => NatureEvenementEssai::RENOUVELLEMENT->value,
        'date_debut' => now()->format('Y-m-d'),
        'date_fin' => now()->addDays(30)->format('Y-m-d'),
        'duree_jours' => 30,
        'commentaire' => 'Renouvellement pour évaluation complémentaire',
    ])->assertRedirect();

    $evenement = EvenementEssai::where('contrat_id', $this->contrat->id)->first();
    expect($evenement)->not->toBeNull();
    expect($evenement->statut)->toBe(StatutEvenementEssai::EN_ATTENTE);
});

it('valide un événement d\'essai', function () {
    $evenement = EvenementEssai::create([
        'entreprise_id' => 1,
        'contrat_id' => $this->contrat->id,
        'cle_soumission' => \Illuminate\Support\Str::uuid(),
        'nature' => NatureEvenementEssai::CONFIRMATION->value,
        'statut' => StatutEvenementEssai::EN_ATTENTE->value,
        'details' => ['commentaire' => 'Test'],
        'cree_par' => $this->admin->id,
        'etat' => 1,
    ]);

    $this->post(route('contrats.evenements-essai.valider', $evenement), [
        'note_decision' => 'Confirmé après évaluation favorable',
    ])->assertRedirect();

    expect($evenement->fresh()->statut)->toBe(StatutEvenementEssai::VALIDE);
});

it('refuse la décision sur un événement déjà traité', function () {
    $evenement = EvenementEssai::create([
        'entreprise_id' => 1,
        'contrat_id' => $this->contrat->id,
        'cle_soumission' => \Illuminate\Support\Str::uuid(),
        'nature' => NatureEvenementEssai::CONFIRMATION->value,
        'statut' => StatutEvenementEssai::VALIDE->value,
        'details' => [],
        'cree_par' => $this->admin->id,
        'etat' => 1,
    ]);

    $this->post(route('contrats.evenements-essai.valider', $evenement), [
        'note_decision' => 'Nouvelle décision',
    ])->assertForbidden();
});