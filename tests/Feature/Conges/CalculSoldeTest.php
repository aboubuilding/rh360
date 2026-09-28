<?php

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Conges\Models\SoldeConge;
use App\Domain\Conges\Models\TypeConge;
use App\Domain\Conges\Services\CalculateurSolde;
use App\Domain\Personnel\Models\Salarie;

beforeEach(function () {
    $this->seed();
    $this->admin = Utilisateur::where('role', Utilisateur::ROLE_SUPER_ADMIN)->first();
    $this->actingAs($this->admin);
});

it('calcule le disponible correctement', function () {
    $solde = SoldeConge::factory()->create([
        'entreprise_id' => 1,
        'solde_ouverture' => 5,
        'acquis' => 30,
        'ajustement' => 2,
        'reserve' => 10,
        'consomme' => 8,
    ]);

    // (5 + 30 + 2) - (10 + 8) = 19
    expect($solde->disponible)->toBe(19.0);
});

it('crée un solde avec acquisition prorata temporis pour embauche en cours d\'année', function () {
    $salarie = Salarie::first();
    $salarie->update(['date_embauche' => now()->startOfYear()->addMonths(6)]);

    $type = TypeConge::where('code', 'CA')->first();
    $calculateur = app(CalculateurSolde::class);

    $solde = $calculateur->creer($salarie, $type, now()->year);

    // 6 mois de travail sur 12 → ~15 jours acquis
    expect((float) $solde->acquis)->toBeGreaterThan(10);
    expect((float) $solde->acquis)->toBeLessThan(20);
});

it('reporte le disponible de l\'année précédente', function () {
    $salarie = Salarie::first();
    $type = TypeConge::where('code', 'CA')->first();

    // Créer un solde pour l'année précédente avec un disponible de 10
    SoldeConge::factory()->create([
        'entreprise_id' => 1,
        'salarie_id' => $salarie->id,
        'type_conge_id' => $type->id,
        'annee' => now()->year - 1,
        'acquis' => 30,
        'consomme' => 20,
    ]);

    $calculateur = app(CalculateurSolde::class);
    $soldeNouveau = $calculateur->creer($salarie, $type, now()->year);

    expect((float) $soldeNouveau->solde_ouverture)->toBe(10.0);
});

it('liste les soldes d\'une année', function () {
    $this->get(route('conges.soldes.index', ['annee' => now()->year]))
        ->assertOk()
        ->assertSee('Soldes');
});

it('affiche les soldes d\'un salarié', function () {
    $salarie = Salarie::first();
    $this->get(route('conges.soldes.pour-salarie', $salarie))
        ->assertOk()
        ->assertSee($salarie->nom_complet);
});