<?php

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Conges\Enums\StatutDemandeConge;
use App\Domain\Conges\Models\DemandeConge;
use App\Domain\Conges\Models\SoldeConge;
use App\Domain\Conges\Models\TypeConge;
use App\Domain\Conges\Services\CalculateurSolde;
use App\Domain\Personnel\Models\Salarie;

beforeEach(function () {
    $this->seed();
    $this->admin = Utilisateur::where('role', Utilisateur::ROLE_SUPER_ADMIN)->first();
    $this->actingAs($this->admin);
    $this->salarie = Salarie::first();
    $this->typeConge = TypeConge::where('code', 'CA')->first();
});

it('soumet une demande brouillon', function () {
    $demande = DemandeConge::factory()->create([
        'entreprise_id' => 1,
        'salarie_id' => $this->salarie->id,
        'type_conge_id' => $this->typeConge->id,
        'statut' => StatutDemandeConge::BROUILLON->value,
        'date_debut' => now()->addDays(15),
        'date_reprise' => now()->addDays(29),
        'duree_jours' => 14,
    ]);

    $this->post(route('conges.demandes.soumettre', $demande))->assertRedirect();
    expect($demande->fresh()->statut)->toBe(StatutDemandeConge::SOUMISE);
});

it('autorise une demande soumise si le solde est suffisant', function () {
    // Créer un solde suffisant
    SoldeConge::factory()->create([
        'entreprise_id' => 1,
        'salarie_id' => $this->salarie->id,
        'type_conge_id' => $this->typeConge->id,
        'annee' => now()->year,
        'acquis' => 30,
    ]);

    $demande = DemandeConge::factory()->create([
        'entreprise_id' => 1,
        'salarie_id' => $this->salarie->id,
        'type_conge_id' => $this->typeConge->id,
        'statut' => StatutDemandeConge::SOUMISE->value,
        'date_debut' => now()->addDays(15),
        'duree_jours' => 14,
    ]);

    $this->post(route('conges.demandes.autoriser', $demande), [
        'reference_acte' => 'ACT-2026-0001',
    ])->assertRedirect();

    expect($demande->fresh()->statut)->toBe(StatutDemandeConge::AUTORISEE);
});

it('refuse d\'autoriser si le solde est insuffisant', function () {
    // Solde insuffisant
    SoldeConge::factory()->create([
        'entreprise_id' => 1,
        'salarie_id' => $this->salarie->id,
        'type_conge_id' => $this->typeConge->id,
        'annee' => now()->year,
        'acquis' => 5,
    ]);

    $demande = DemandeConge::factory()->create([
        'entreprise_id' => 1,
        'salarie_id' => $this->salarie->id,
        'type_conge_id' => $this->typeConge->id,
        'statut' => StatutDemandeConge::SOUMISE->value,
        'date_debut' => now()->addDays(15),
        'duree_jours' => 14,
    ]);

    $this->post(route('conges.demandes.autoriser', $demande))
        ->assertSessionHas('error');
});

it('refuse une demande avec motif obligatoire', function () {
    $demande = DemandeConge::factory()->soumise()->create([
        'entreprise_id' => 1,
        'salarie_id' => $this->salarie->id,
        'type_conge_id' => $this->typeConge->id,
    ]);

    $this->post(route('conges.demandes.refuser', $demande), [])
        ->assertSessionHasErrors('motif');
});

it('réserve les jours dans le solde à l\'autorisation', function () {
    $solde = SoldeConge::factory()->create([
        'entreprise_id' => 1,
        'salarie_id' => $this->salarie->id,
        'type_conge_id' => $this->typeConge->id,
        'annee' => now()->year,
        'acquis' => 30,
    ]);

    $demande = DemandeConge::factory()->create([
        'entreprise_id' => 1,
        'salarie_id' => $this->salarie->id,
        'type_conge_id' => $this->typeConge->id,
        'statut' => StatutDemandeConge::SOUMISE->value,
        'date_debut' => now()->addDays(15),
        'duree_jours' => 14,
    ]);

    $this->post(route('conges.demandes.autoriser', $demande));
    app(CalculateurSolde::class)->resynchroniser($solde->fresh());

    expect((float) $solde->fresh()->reserve)->toBe(14.0);
});

it('confirme la reprise et bascule en consommé', function () {
    $solde = SoldeConge::factory()->create([
        'entreprise_id' => 1,
        'salarie_id' => $this->salarie->id,
        'type_conge_id' => $this->typeConge->id,
        'annee' => now()->year,
        'acquis' => 30,
    ]);

    $demande = DemandeConge::factory()->enCours()->create([
        'entreprise_id' => 1,
        'salarie_id' => $this->salarie->id,
        'type_conge_id' => $this->typeConge->id,
        'duree_jours' => 14,
    ]);

    $this->post(route('conges.demandes.confirmer-reprise', $demande))->assertRedirect();

    expect($demande->fresh()->statut)->toBe(StatutDemandeConge::REPRISE_CONFIRMEE);
});