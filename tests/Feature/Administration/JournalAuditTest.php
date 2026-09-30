<?php

/*
| CDC §4 8.5 et §6 — Journal d'audit : toute création, modification, suppression
| ou modification de permission est tracée (utilisateur, date, entité, détail
| des champs modifiés). Accès : Super administrateur, Administrateur, DRH, Auditeur.
*/

use App\Domain\Administration\Models\JournalAudit;
use App\Domain\Administration\Models\Utilisateur;

beforeEach(function () {
    $this->seed();
    $this->admin = Utilisateur::where('role', Utilisateur::ROLE_SUPER_ADMIN)->first();
    $this->actingAs($this->admin);
});

it('liste le journal d\'audit', function () {
    $this->get(route('admin.audit.index'))->assertOk();
});

it('affiche une entrée du journal', function () {
    $cible = Utilisateur::factory()->create(['identifiant' => 'trace_affichage']);
    $entree = JournalAudit::where('entite', 'Utilisateur')->where('entite_id', $cible->id)->firstOrFail();

    $this->get(route('admin.audit.show', $entree))->assertOk();
});

it('trace la création avec l\'auteur et l\'entité', function () {
    $cible = Utilisateur::factory()->create(['identifiant' => 'trace_creation']);

    $entree = JournalAudit::where('entite', 'Utilisateur')->where('entite_id', $cible->id)
        ->where('action', 'created')->first();

    expect($entree)->not->toBeNull();
    expect($entree->utilisateur_id)->toBe($this->admin->id);
    expect($entree->entreprise_id)->toBe(1);
});

it('trace une modification avec les valeurs avant / après', function () {
    $cible = Utilisateur::factory()->role(Utilisateur::ROLE_RH)->create();

    $cible->update(['role' => Utilisateur::ROLE_DRH]);

    $entree = JournalAudit::where('entite', 'Utilisateur')->where('entite_id', $cible->id)
        ->where('action', 'updated')->latest('id')->first();

    expect($entree->details['role'])->toBe(['avant' => Utilisateur::ROLE_RH, 'apres' => Utilisateur::ROLE_DRH]);
});

it('ne trace pas une modification sans changement effectif', function () {
    $cible = Utilisateur::factory()->create();
    $avant = JournalAudit::where('entite_id', $cible->id)->where('action', 'updated')->count();

    $cible->update(['nom_complet' => $cible->nom_complet]);

    expect(JournalAudit::where('entite_id', $cible->id)->where('action', 'updated')->count())->toBe($avant);
});

it('trace la modification de la matrice des permissions', function () {
    $avant = JournalAudit::where('entite', 'PermissionRole')->count();

    $this->put(route('admin.permissions.matrice.update'), [
        'role' => Utilisateur::ROLE_MANAGER,
        'permissions' => ['dashboard.view'],
    ])->assertRedirect();

    expect(JournalAudit::where('entite', 'PermissionRole')->count())->toBeGreaterThan($avant);
});

it('trace les exceptions individuelles de permission', function () {
    $cible = Utilisateur::factory()->role(Utilisateur::ROLE_RH)->create();

    $this->put(route('admin.permissions.exceptions.update', $cible), [
        'accorder' => ['contrats.validate'],
    ])->assertRedirect();

    expect(JournalAudit::where('entite', 'PermissionUtilisateur')->where('action', 'created')->exists())->toBeTrue();
});

it('filtre par action et par entité', function () {
    Utilisateur::factory()->create(['nom_complet' => 'Filtre Audit']);

    $this->get(route('admin.audit.index', ['action' => 'created', 'entite' => 'Utilisateur']))
        ->assertOk()
        ->assertViewHas('entrees', fn ($entrees) => $entrees->isNotEmpty()
            && $entrees->every(fn ($e) => $e->action === 'created' && $e->entite === 'Utilisateur'));
});

it('filtre par période (bornes incluses)', function () {
    Utilisateur::factory()->create(); // entrée d'audit datée d'aujourd'hui

    $this->get(route('admin.audit.index', ['du' => now()->format('Y-m-d'), 'au' => now()->format('Y-m-d')]))
        ->assertOk()
        ->assertViewHas('entrees', fn ($entrees) => $entrees->isNotEmpty());

    $this->get(route('admin.audit.index', ['au' => now()->subYear()->format('Y-m-d')]))
        ->assertOk()
        ->assertViewHas('entrees', fn ($entrees) => $entrees->isEmpty());
});

it('ouvre le journal aux profils prévus et le ferme aux autres', function (string $role, bool $autorise) {
    $u = Utilisateur::factory()->role($role)->create();

    $reponse = $this->actingAs($u)->get(route('admin.audit.index'));
    $autorise ? $reponse->assertOk() : $reponse->assertForbidden();
})->with([
    'Administrateur' => [Utilisateur::ROLE_ADMIN, true],
    'DRH' => [Utilisateur::ROLE_DRH, true],
    'Auditeur' => [Utilisateur::ROLE_AUDITEUR, true],
    'Responsable RH' => [Utilisateur::ROLE_RH, false],
    'Manager' => [Utilisateur::ROLE_MANAGER, false],
    'Direction' => [Utilisateur::ROLE_DIRECTION, false],
]);

it('trace les validations et fusions métier (contrats, actes, salariés)')
    ->todo('CDC §6 : seuls Entreprise, Utilisateur, PermissionRole et PermissionUtilisateur sont observés par AuditObserver');

it('exporte le journal')
    ->todo('CDC §4 8.5 : export du journal d\'audit non implémenté');
