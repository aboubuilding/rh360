<?php

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Administration\Services\VerificateurMotDePasseLegacy;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->seed();
});

it('vérifie un mot de passe PBKDF2 et le rehashe en bcrypt', function () {
    $clair = 'Secret@2026';
    $iterations = 10000;
    $salt = 'sel_test';
    $hash = 'pbkdf2_sha256$'.$iterations.'$'.$salt.'$'
        . base64_encode(hash_pbkdf2('sha256', $clair, $salt, $iterations, 0, true));

    $user = Utilisateur::create([
        'entreprise_id' => 1,
        'nom_complet' => 'Legacy',
        'identifiant' => 'legacy_test',
        'password' => $hash,
        'role' => Utilisateur::ROLE_RH,
        'actif' => true,
        'etat' => 1,
    ]);

    $verif = app(VerificateurMotDePasseLegacy::class);
    expect($verif->verifier($user, $clair))->toBeTrue();

    $user->refresh();
    expect($verif->estFormatLegacy($user->password))->toBeFalse();
    expect(Hash::check($clair, $user->password))->toBeTrue();
});