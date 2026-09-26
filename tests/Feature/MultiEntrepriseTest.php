<?php

use App\Domain\Administration\Models\Entreprise;
use App\Domain\Administration\Models\Utilisateur;

it('filtre les données par entreprise', function () {
    $autre = Entreprise::create([
        'nom' => 'Autre', 'pays' => 'Togo', 'devise' => 'XOF', 'etat' => 1,
    ]);

    $user = Utilisateur::first();
    $this->actingAs($user);

    $utilisateurs = Utilisateur::all();
    expect($utilisateurs->pluck('entreprise_id')->unique()->all())->toBe([1]);
});