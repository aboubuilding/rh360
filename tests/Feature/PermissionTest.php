<?php

use App\Domain\Administration\Models\PermissionRole;
use App\Domain\Administration\Models\Utilisateur;

it('autorise le super admin sur toute permission', function () {
    $user = Utilisateur::first();
    expect($user->peut('salaries.manage'))->toBeTrue();
    expect($user->peut('nimporte.quoi'))->toBeTrue();
});

it('applique la matrice des rôles', function () {
    $entrepriseId = 1;
    $drh = Utilisateur::create([
        'entreprise_id' => $entrepriseId,
        'nom_complet' => 'DRH Test',
        'identifiant' => 'drh_test',
        'password' => bcrypt('x'),
        'role' => Utilisateur::ROLE_DRH,
        'actif' => true,
        'etat' => 1,
    ]);

    expect($drh->peut('contrats.validate'))->toBeTrue();
    expect($drh->peut('admin.utilisateurs.manage'))->toBeFalse();
});