<?php

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Paie\Models\HeureSupplementaire;
use App\Domain\Paie\Models\PeriodePaie;
use App\Domain\Paie\Services\CalculateurHeuresSupp;
use App\Domain\Personnel\Models\Salarie;

beforeEach(function () {
    $this->seed();
    $this->admin = Utilisateur::where('role', Utilisateur::ROLE_SUPER_ADMIN)->first();
    $this->actingAs($this->admin);
});

it('calcule le taux horaire correctement (base 173.33 h)', function () {
    $taux = app(CalculateurHeuresSupp::class)->calculerTauxHoraire(250000);
    expect($taux)->toBe(round(250000 / 173.33, 4));
});

it('calcule le montant total d\'un acte HS', function () {
    $salarie = Salarie::where('actif', true)->first();
    $periode = PeriodePaie::factory()->create(['entreprise_id' => 1, 'annee' => now()->year, 'mois' => 11]);

    $acte = HeureSupplementaire::create([
        'entreprise_id' => 1,
        'salarie_id' => $salarie->id,
        'reference' => 'HS-TEST-001',
        'debut_travail' => now()->startOfMonth(),
        'fin_travail' => now()->endOfMonth(),
        'periode_paiement_id' => $periode->id,
        'heures_hs20' => 10,
        'heures_hs40' => 5,
        'salaire_base_fige' => 250000,
        'taux_horaire' => 1442.50,
        'cree_par' => $this->admin->id,
        'etat' => 1,
    ]);

    // HS 20 % : 10 × 1442.50 × 1.20 = 17 310.00
    // HS 40 % : 5 × 1442.50 × 1.40 = 10 097.50
    // Total = 27 407.50
    $montant = $acte->montantTotal();

    expect($montant)->toBe(27407.5);
});

it('ajoute un acte HS via l\'action', function () {
    $salarie = Salarie::where('actif', true)->first();
    $periode = PeriodePaie::factory()->create(['entreprise_id' => 1, 'annee' => now()->year, 'mois' => 10]);

    $this->post(route('paie.heures-supp.store'), [
        'salarie_id' => $salarie->id,
        'reference' => 'HS-ACTION-001',
        'debut_travail' => now()->startOfMonth()->format('Y-m-d'),
        'fin_travail' => now()->endOfMonth()->format('Y-m-d'),
        'periode_paiement_id' => $periode->id,
        'heures_hs20' => 8,
    ])->assertRedirect();

    $this->assertDatabaseHas('heures_supplementaires', [
        'reference' => 'HS-ACTION-001',
        'salarie_id' => $salarie->id,
    ]);
});