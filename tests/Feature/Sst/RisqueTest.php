<?php

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Personnel\Models\Salarie;
use App\Domain\Sst\Enums\FamilleRisque;
use App\Domain\Sst\Enums\NiveauRisque;
use App\Domain\Sst\Models\Risque;
use App\Domain\Sst\Services\CalculateurScoreRisque;

beforeEach(function () {
    $this->seed();
    $this->admin = Utilisateur::where('role', Utilisateur::ROLE_SUPER_ADMIN)->first();
    $this->actingAs($this->admin);
    $this->salarie = Salarie::first();
});

it('calcule le score correctement selon gravité × probabilité', function () {
    $calculateur = app(CalculateurScoreRisque::class);

    expect($calculateur->calculer(1, 1)['score'])->toBe(1);
    expect($calculateur->calculer(1, 1)['niveau'])->toBe(NiveauRisque::FAIBLE);

    expect($calculateur->calculer(3, 3)['score'])->toBe(9);
    expect($calculateur->calculer(3, 3)['niveau'])->toBe(NiveauRisque::MOYEN);

    expect($calculateur->calculer(4, 4)['score'])->toBe(16);
    expect($calculateur->calculer(4, 4)['niveau'])->toBe(NiveauRisque::ELEVE);

    expect($calculateur->calculer(5, 5)['score'])->toBe(25);
    expect($calculateur->calculer(5, 5)['niveau'])->toBe(NiveauRisque::CRITIQUE);
});

it('rejette une gravité ou probabilité hors plage', function () {
    $calculateur = app(CalculateurScoreRisque::class);

    expect(fn () => $calculateur->calculer(0, 3))->toThrow(\InvalidArgumentException::class);
    expect(fn () => $calculateur->calculer(3, 6))->toThrow(\InvalidArgumentException::class);
});

it('crée un risque', function () {
    $this->post(route('sst.risques.store'), [
        'intitule' => 'Risque de chute',
        'famille' => FamilleRisque::PHYSIQUE->value,
        'site' => 'Site Test',
        'activite' => 'Travaux en hauteur',
        'danger' => 'Chute depuis échafaudage',
        'consequences' => 'Fractures multiples',
        'responsable_salarie_id' => $this->salarie->id,
    ])->assertRedirect();

    $this->assertDatabaseHas('risques', [
        'intitule' => 'Risque de chute',
        'statut' => 'active',
        'etat' => 1,
    ]);
});

it('évalue un risque et calcule automatiquement le niveau', function () {
    $risque = Risque::factory()->create(['entreprise_id' => 1]);

    $this->post(route('sst.risques.evaluer', $risque), [
        'gravite' => 5,
        'probabilite' => 4,
        'mesures_existantes' => 'EPI disponibles',
        'justification' => 'Évaluation périodique annuelle',
    ])->assertRedirect();

    $evaluation = $risque->evaluations()->first();
    expect($evaluation)->not->toBeNull();
    expect($evaluation->score)->toBe(20);
    expect($evaluation->niveau)->toBe(NiveauRisque::CRITIQUE);
});

it('archive un risque avec motif obligatoire', function () {
    $risque = Risque::factory()->create(['entreprise_id' => 1]);

    // Sans motif → erreur
    $this->post(route('sst.risques.archiver', $risque), [])
        ->assertSessionHasErrors('motif');

    // Avec motif → OK
    $this->post(route('sst.risques.archiver', $risque), [
        'motif' => 'Risque éliminé par suppression du poste',
    ])->assertRedirect();

    expect($risque->fresh()->statut)->toBe('archived');
});