<?php

use App\Domain\Administration\Models\PermissionRole;
use App\Domain\Administration\Models\Utilisateur;

beforeEach(function () {
    $this->seed();
    $this->admin = Utilisateur::where('role', Utilisateur::ROLE_SUPER_ADMIN)->first();
    $this->actingAs($this->admin);
});

it('affiche la matrice', function () {
    $this->get(route('admin.permissions.matrice'))->assertOk()->assertSee('Matrice');
});

it('met à jour la matrice d\'un rôle', function () {
    $this->put(route('admin.permissions.matrice.update'), [
        'role' => Utilisateur::ROLE_RH,
        'permissions' => ['salaries.view', 'contrats.view'],
    ])->assertRedirect();

    expect(PermissionRole::where('role', Utilisateur::ROLE_RH)
        ->where('permission', 'salaries.view')
        ->where('autorise', true)->exists())->toBeTrue();

    expect(PermissionRole::where('role', Utilisateur::ROLE_RH)
        ->where('permission', 'admin.utilisateurs.manage')
        ->where('autorise', true)->exists())->toBeFalse();
});