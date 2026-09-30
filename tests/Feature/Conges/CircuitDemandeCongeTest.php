<?php

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Conges\Enums\StatutDemandeConge;
use App\Domain\Conges\Models\DemandeConge;
use App\Domain\Conges\Models\SoldeConge;
use App\Domain\Conges\Models\TypeConge;
use App\Domain\Conges\Services\CalculateurSolde;
use App\Domain\Personnel\Models\Salarie;

beforeEach(function () {
    // Date figée en milieu d'exercice : les demandes « dans 15 jours » restent sur l'année du solde.
    $this->travelTo(now()->setDate(2026, 3, 2)->setTime(9, 0));
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

    // Le seed peut déjà avoir réservé des jours pour ce salarié : on mesure l'écart.
    $reserveAvant = (float) app(CalculateurSolde::class)->resynchroniser($solde->fresh())->reserve;

    $this->post(route('conges.demandes.autoriser', $demande))->assertRedirect();
    $apres = app(CalculateurSolde::class)->resynchroniser($solde->fresh());

    expect((float) $apres->reserve - $reserveAvant)->toBe(14.0);
    expect((float) $apres->consomme)->toBe(0.0);
});

it('ne réserve aucun jour pour une demande refusée', function () {
    $solde = SoldeConge::factory()->create([
        'entreprise_id' => 1,
        'salarie_id' => $this->salarie->id,
        'type_conge_id' => $this->typeConge->id,
        'annee' => now()->year,
        'acquis' => 30,
    ]);
    $reserveAvant = (float) app(CalculateurSolde::class)->resynchroniser($solde->fresh())->reserve;

    $demande = DemandeConge::factory()->soumise()->create([
        'entreprise_id' => 1,
        'salarie_id' => $this->salarie->id,
        'type_conge_id' => $this->typeConge->id,
        'duree_jours' => 10,
    ]);

    $this->post(route('conges.demandes.refuser', $demande), ['motif' => 'Nécessités de service'])
        ->assertRedirect();

    expect($demande->fresh()->statut)->toBe(StatutDemandeConge::REFUSEE);
    expect((float) app(CalculateurSolde::class)->resynchroniser($solde->fresh())->reserve)->toBe($reserveAvant);
});

it('confirme la reprise et bascule en consommé', function () {
    $solde = SoldeConge::factory()->create([
        'entreprise_id' => 1,
        'salarie_id' => $this->salarie->id,
        'type_conge_id' => $this->typeConge->id,
        'annee' => now()->year,
        'acquis' => 30,
    ]);

    // Congé en cours dont la date de reprise prévue est atteinte
    $demande = DemandeConge::factory()->enRetard()->create([
        'entreprise_id' => 1,
        'salarie_id' => $this->salarie->id,
        'type_conge_id' => $this->typeConge->id,
        'duree_jours' => 14,
    ]);
    $calc = app(CalculateurSolde::class);
    $avant = $calc->resynchroniser($solde->fresh());

    $this->post(route('conges.demandes.confirmer-reprise', $demande))->assertRedirect();

    expect($demande->fresh()->statut)->toBe(StatutDemandeConge::REPRISE_CONFIRMEE);

    // Bascule des jours réservés en jours consommés
    $apres = $calc->resynchroniser($solde->fresh());
    expect((float) $apres->consomme - (float) $avant->consomme)->toBe(14.0);
    expect((float) $avant->reserve - (float) $apres->reserve)->toBe(14.0);
});

it('refuse de confirmer la reprise avant la date prévue', function () {
    $demande = DemandeConge::factory()->enCours()->create([ // reprise prévue dans 9 jours
        'entreprise_id' => 1,
        'salarie_id' => $this->salarie->id,
        'type_conge_id' => $this->typeConge->id,
    ]);

    $this->post(route('conges.demandes.confirmer-reprise', $demande))->assertForbidden();

    expect($demande->fresh()->statut)->toBe(StatutDemandeConge::EN_COURS);
});

it('bloque aussi la reprise anticipée au niveau de l\'action métier', function () {
    $demande = DemandeConge::factory()->enCours()->create([
        'entreprise_id' => 1,
        'salarie_id' => $this->salarie->id,
        'type_conge_id' => $this->typeConge->id,
    ]);

    app(\App\Domain\Conges\Actions\ConfirmerReprise::class)->executer($demande);
})->throws(DomainException::class, 'date prévue');

it('confirme la reprise d\'un congé programmé dont les dates sont passées', function () {
    $demande = DemandeConge::factory()->create([
        'entreprise_id' => 1,
        'salarie_id' => $this->salarie->id,
        'type_conge_id' => $this->typeConge->id,
        'statut' => StatutDemandeConge::PROGRAMMEE->value,
        'date_debut' => now()->subDays(10),
        'date_reprise' => now()->subDay(),
    ]);

    $this->post(route('conges.demandes.confirmer-reprise', $demande))->assertRedirect();

    expect($demande->fresh()->statut)->toBe(StatutDemandeConge::REPRISE_CONFIRMEE);
});

it('refuse de démarrer un congé avant sa date de départ', function () {
    $demande = DemandeConge::factory()->create([
        'entreprise_id' => 1,
        'salarie_id' => $this->salarie->id,
        'type_conge_id' => $this->typeConge->id,
        'statut' => StatutDemandeConge::PROGRAMMEE->value,
        'date_debut' => now()->addDays(3),
        'date_reprise' => now()->addDays(10),
    ]);

    $this->post(route('conges.demandes.demarrer', $demande))->assertForbidden();
    expect($demande->fresh()->statut)->toBe(StatutDemandeConge::PROGRAMMEE);
});

it('démarre un congé autorisé dont la date de départ est atteinte, en le programmant au passage', function () {
    $demande = DemandeConge::factory()->create([
        'entreprise_id' => 1,
        'salarie_id' => $this->salarie->id,
        'type_conge_id' => $this->typeConge->id,
        'statut' => StatutDemandeConge::AUTORISEE->value,
        'date_debut' => now()->startOfDay(),
        'date_reprise' => now()->addDays(7),
    ]);

    $this->post(route('conges.demandes.demarrer', $demande))->assertRedirect();
    expect($demande->fresh()->statut)->toBe(StatutDemandeConge::EN_COURS);
});

it('interdit toute transition depuis un état final', function () {
    $demande = DemandeConge::factory()->terminee()->create([
        'entreprise_id' => 1,
        'salarie_id' => $this->salarie->id,
        'type_conge_id' => $this->typeConge->id,
    ]);

    $this->post(route('conges.demandes.annuler', $demande), ['motif' => 'Trop tard'])->assertForbidden();
    expect($demande->fresh()->statut)->toBe(StatutDemandeConge::REPRISE_CONFIRMEE);
});