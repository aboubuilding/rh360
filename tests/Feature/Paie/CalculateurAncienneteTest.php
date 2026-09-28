<?php

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Paie\Services\CalculateurAnciennete;
use App\Domain\Personnel\Models\Salarie;
use Carbon\Carbon;

beforeEach(function () {
    $this->seed();
    $this->admin = Utilisateur::where('role', Utilisateur::ROLE_SUPER_ADMIN)->first();
    $this->actingAs($this->admin);
});

it('retourne 0 % pour un salarié avec moins de 2 ans d\'ancienneté', function () {
    $salarie = Salarie::where('actif', true)->first();
    $salarie->update(['date_embauche' => now()->subYear()]);

    $taux = app(CalculateurAnciennete::class)->calculerTaux($salarie, now());

    expect($taux)->toBe(0.0);
});

it('retourne 3 % pour un salarié avec 2 ans d\'ancienneté', function () {
    $salarie = Salarie::where('actif', true)->first();
    $salarie->update(['date_embauche' => now()->subYears(2)]);

    $taux = app(CalculateurAnciennete::class)->calculerTaux($salarie, now());

    expect($taux)->toBe(3.0);
});

it('retourne 5 % pour un salarié avec 4 ans d\'ancienneté', function () {
    $salarie = Salarie::where('actif', true)->first();
    $salarie->update(['date_embauche' => now()->subYears(4)]);

    $taux = app(CalculateurAnciennete::class)->calculerTaux($salarie, now());

    // 3 % + (4 - 2) × 1 % = 5 %
    expect($taux)->toBe(5.0);
});

it('plafonne le taux à 15 %', function () {
    $salarie = Salarie::where('actif', true)->first();
    $salarie->update(['date_embauche' => now()->subYears(30)]);

    $taux = app(CalculateurAnciennete::class)->calculerTaux($salarie, now());

    expect($taux)->toBe(15.0);
});

it('calcule la prime d\'ancienneté sur une base donnée', function () {
    $salarie = Salarie::where('actif', true)->first();
    $salarie->update(['date_embauche' => now()->subYears(4)]);

    $prime = app(CalculateurAnciennete::class)->calculerPrime($salarie, 200000, now());

    // 5 % de 200 000 = 10 000
    expect($prime)->toBe(10000.0);
});