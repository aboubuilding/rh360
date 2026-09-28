<?php

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Paie\Services\CalculateurCotisations;

beforeEach(function () {
    $this->seed();
    $this->admin = Utilisateur::where('role', Utilisateur::ROLE_SUPER_ADMIN)->first();
    $this->actingAs($this->admin);
});

it('calcule les cotisations CNSS et AMU', function () {
    $resultat = app(CalculateurCotisations::class)->calculer(1, 300000, now());

    // CNSS 4 % de 300 000 = 12 000
    // AMU 5 % de 300 000 = 15 000
    // Total = 27 000
    expect($resultat['total_salarial'])->toBe(27000.0);

    $codes = collect($resultat['lignes'])->pluck('code')->toArray();
    expect($codes)->toContain('CNSS');
    expect($codes)->toContain('AMU');
});

it('calcule uniquement les cotisations déductibles', function () {
    $total = app(CalculateurCotisations::class)->cotisationsDeductibles(1, 300000, now());

    // CNSS 4 % + AMU 5 % = 9 % → 27 000
    expect($total)->toBe(27000.0);
});