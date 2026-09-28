<?php

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Paie\Models\RegleIrpp;
use App\Domain\Paie\Services\CalculateurIRPP;
use App\Domain\Personnel\Models\Salarie;

beforeEach(function () {
    $this->seed();
    $this->admin = Utilisateur::where('role', Utilisateur::ROLE_SUPER_ADMIN)->first();
    $this->actingAs($this->admin);
});

it('calcule l\'IRPP progressif par tranches', function () {
    $regle = RegleIrpp::where('entreprise_id', 1)->where('actif', true)->first();

    // Barème : [600000, 1200000, 2400000, 3600000, 5000000, 10000000]
    // Taux  : [0, 5, 10, 15, 20, 25]

    // Base 500 000 → tranche 1 uniquement (0 %) = 0
    expect((float) $regle->calculerIrpp(500000))->toBe(0.0);

    // Base 1 000 000 → 0 × 600 000 + 5 % × 400 000 = 20 000
    expect((float) $regle->calculerIrpp(1000000))->toBe(20000.0);

    // Base 2 000 000 → 0 + 5 % × 600 000 + 10 % × 800 000 = 30 000 + 80 000 = 110 000
    expect((float) $regle->calculerIrpp(2000000))->toBe(110000.0);
});

it('calcule l\'abattement professionnel avec plafond', function () {
    $regle = RegleIrpp::where('entreprise_id', 1)->where('actif', true)->first();

    // 28 % de 500 000 = 140 000 (sous plafond)
    expect((float) $regle->calculerAbattement(500000))->toBe(140000.0);

    // 28 % de 100 000 000 = 28 000 000 → plafonné à 10 000 000
    expect((float) $regle->calculerAbattement(100000000))->toBe(10000000.0);
});

it('calcule la déduction pour charges de famille', function () {
    $regle = RegleIrpp::where('entreprise_id', 1)->where('actif', true)->first();

    // 3 charges × 10 000 = 30 000
    expect((float) $regle->calculerDeductionCharges(3))->toBe(30000.0);

    // 10 charges → plafonné à 6 charges × 10 000 = 60 000
    expect((float) $regle->calculerDeductionCharges(10))->toBe(60000.0);
});

it('calcule l\'IRPP complet sur un salaire de 300 000 FCFA', function () {
    $salarie = Salarie::where('actif', true)->first();

    $resultat = app(CalculateurIRPP::class)->calculer(
        $salarie,
        brutImposable: 300000,
        cotisationsDeductibles: 27000, // CNSS + AMU
        date: now(),
    );

    // Brut imposable = 300 000
    // Abattement = 28 % × 300 000 = 84 000
    // Charges = 0
    // Base = 300 000 - 27 000 - 84 000 - 0 = 189 000
    // IRPP sur 189 000 → tranche 1 uniquement (0 %) = 0
    expect($resultat['abattement_professionnel'])->toBe(84000.0);
    expect($resultat['base_imposable'])->toBe(189000.0);
    expect($resultat['montant_irpp'])->toBe(0.0);
});