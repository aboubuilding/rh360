<?php

/*
| CDC §6 — Permissions à deux niveaux : un rôle porte des permissions par défaut
| (matrice paramétrable) et chaque utilisateur peut recevoir des exceptions
| individuelles. Permission effective = exception si elle existe, sinon rôle.
*/

use App\Domain\Administration\Models\PermissionUtilisateur;
use App\Domain\Administration\Models\Utilisateur;

beforeEach(function () {
    $this->seed();
    $this->superAdmin = Utilisateur::where('identifiant', 'superadmin')->first();
    $this->rh = Utilisateur::factory()->role(Utilisateur::ROLE_RH)->create();
});

it('autorise le super admin sur toute permission', function () {
    expect($this->superAdmin->peut('salaries.manage'))->toBeTrue();
    expect($this->superAdmin->peut('nimporte.quoi'))->toBeTrue();
});

it('applique la matrice des rôles', function () {
    $drh = Utilisateur::factory()->role(Utilisateur::ROLE_DRH)->create();

    expect($drh->peut('contrats.validate'))->toBeTrue();
    expect($drh->peut('admin.utilisateurs.manage'))->toBeFalse();
});

it('refuse une permission inconnue à un utilisateur non super admin', function () {
    expect($this->rh->peut('nimporte.quoi'))->toBeFalse();
});

it('accorde une permission par exception individuelle', function () {
    expect($this->rh->peut('contrats.validate'))->toBeFalse();

    PermissionUtilisateur::create([
        'entreprise_id' => 1, 'utilisateur_id' => $this->rh->id,
        'permission' => 'contrats.validate', 'autorise' => true,
    ]);
    app(\App\Domain\Administration\Services\ServicePermissions::class)->viderCache($this->rh);

    expect($this->rh->peut('contrats.validate'))->toBeTrue();
});

it('retire une permission du rôle par exception individuelle', function () {
    expect($this->rh->peut('salaries.view'))->toBeTrue();

    PermissionUtilisateur::create([
        'entreprise_id' => 1, 'utilisateur_id' => $this->rh->id,
        'permission' => 'salaries.view', 'autorise' => false,
    ]);
    app(\App\Domain\Administration\Services\ServicePermissions::class)->viderCache($this->rh);

    expect($this->rh->peut('salaries.view'))->toBeFalse();
    // Les autres permissions du rôle restent effectives
    expect($this->rh->peut('contrats.view'))->toBeTrue();
});

it('applique immédiatement une exception modifiée depuis l\'écran d\'administration', function () {
    // Première lecture : met la permission en cache
    expect($this->rh->peut('salaries.view'))->toBeTrue();

    $this->actingAs($this->superAdmin)
        ->put(route('admin.permissions.exceptions.update', $this->rh), [
            'accorder' => ['contrats.validate'],
            'retirer' => ['salaries.view'],
        ])->assertRedirect();

    $rh = $this->rh->fresh();
    expect($rh->peut('salaries.view'))->toBeFalse();
    expect($rh->peut('contrats.validate'))->toBeTrue();

    // Et côté HTTP
    $this->actingAs($rh)->get(route('personnel.salaries.index'))->assertForbidden();
});

it('applique immédiatement une modification de la matrice des rôles', function () {
    expect($this->rh->peut('paie.view'))->toBeTrue(); // mis en cache

    $this->actingAs($this->superAdmin)
        ->put(route('admin.permissions.matrice.update'), [
            'role' => Utilisateur::ROLE_RH,
            'permissions' => ['dashboard.view', 'salaries.view'],
        ])->assertRedirect();

    expect($this->rh->fresh()->peut('paie.view'))->toBeFalse();
    expect($this->rh->fresh()->peut('salaries.view'))->toBeTrue();
});

it('ne donne aucune permission à un compte désactivé', function () {
    expect($this->rh->peut('salaries.view'))->toBeTrue();

    $this->rh->update(['actif' => false]);

    expect($this->rh->fresh()->peut('salaries.view'))->toBeFalse();
});

it('réserve la gestion des permissions au Super administrateur et au DRH', function (string $role, bool $attendu) {
    $u = Utilisateur::factory()->role($role)->create();

    $reponse = $this->actingAs($u)->get(route('admin.permissions.matrice'));
    $attendu ? $reponse->assertOk() : $reponse->assertForbidden();
})->with([
    'DRH' => [Utilisateur::ROLE_DRH, true],
    'Administrateur' => [Utilisateur::ROLE_ADMIN, false],
    'Responsable RH' => [Utilisateur::ROLE_RH, false],
    'Auditeur' => [Utilisateur::ROLE_AUDITEUR, false],
]);
