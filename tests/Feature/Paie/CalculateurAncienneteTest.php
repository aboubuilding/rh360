<?php

/*
| Règle d'ancienneté de démonstration : CCIT 2026 — 2 % après deux ans, +1 % par année à partir
| de la quatrième (lecture « cumul »), au plus 35 %, sur le minimum de la catégorie.
*/

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Paie\Models\RegleAnciennete;
use App\Domain\Paie\Services\CalculateurAnciennete;
use App\Domain\Personnel\Models\Salarie;

beforeEach(function () {
    $this->seed();
    $this->admin = Utilisateur::where('role', Utilisateur::ROLE_SUPER_ADMIN)->first();
    $this->actingAs($this->admin);
});

it('retourne 0 % pour un salarié avec moins de 2 ans d\'ancienneté', function () {
    $salarie = Salarie::where('actif', true)->first();
    $salarie->update(['date_embauche' => now()->subYear()]);

    expect(app(CalculateurAnciennete::class)->calculerTaux($salarie, now()))->toBe(0.0);
});

it('retourne 2 % pour un salarié avec 2 ans d\'ancienneté', function () {
    $salarie = Salarie::where('actif', true)->first();
    $salarie->update(['date_embauche' => now()->subYears(2)]);

    expect(app(CalculateurAnciennete::class)->calculerTaux($salarie, now()))->toBe(2.0);
});

it('retourne 3 % pour un salarié avec 4 ans d\'ancienneté', function () {
    $salarie = Salarie::where('actif', true)->first();
    $salarie->update(['date_embauche' => now()->subYears(4)]);

    // 2 % + 1 % pour la quatrième année
    expect(app(CalculateurAnciennete::class)->calculerTaux($salarie, now()))->toBe(3.0);
});

it('plafonne le taux à 35 %', function () {
    $salarie = Salarie::where('actif', true)->first();
    $salarie->update(['date_embauche' => now()->subYears(40)]);

    expect(app(CalculateurAnciennete::class)->calculerTaux($salarie, now()))->toBe(35.0);
});

it('calcule la prime d\'ancienneté sur le salaire de base réel lorsque la règle le prévoit', function () {
    RegleAnciennete::query()->update(['mode_base' => 'salary_base']);
    $salarie = Salarie::where('actif', true)->first();
    $salarie->update(['date_embauche' => now()->subYears(4)]);

    $prime = app(CalculateurAnciennete::class)->calculerPrime($salarie, 200000, now());

    // 3 % de 200 000 = 6 000
    expect($prime)->toBe(6000.0);
});

it('conserve le calcul linéaire d\'une règle existante', function () {
    RegleAnciennete::query()->update(['mode_taux' => 'lineaire', 'taux_initial' => 3, 'taux_max' => 15, 'annee_debut_increment' => null]);
    $salarie = Salarie::where('actif', true)->first();
    $salarie->update(['date_embauche' => now()->subYears(4)]);

    // 3 % + (4 - 2) × 1 % = 5 %
    expect(app(CalculateurAnciennete::class)->calculerTaux($salarie, now()))->toBe(5.0);
});
