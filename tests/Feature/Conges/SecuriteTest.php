<?php

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Conges\Models\DossierMaternite;
use App\Domain\Conges\Models\TypeConge;
use App\Domain\Personnel\Models\Salarie;

beforeEach(function () {
    $this->seed();
});

it('refuse l\'accès au registre maternité sans permission sensible', function () {
    // Créer un utilisateur sans la permission sensitive.social_health
    $utilisateur = Utilisateur::create([
        'entreprise_id' => 1,
        'nom_complet' => 'Sans Sensible',
        'identifiant' => 'sans_sensible',
        'password' => bcrypt('x'),
        'role' => Utilisateur::ROLE_AUDITEUR,
        'actif' => true,
        'etat' => 1,
    ]);

    $this->actingAs($utilisateur)
        ->get(route('conges.maternite.index'))
        ->assertForbidden();
});

it('autorise l\'accès au registre maternité avec la permission', function () {
    // Le super admin a toutes les permissions
    $admin = Utilisateur::where('role', Utilisateur::ROLE_SUPER_ADMIN)->first();
    $this->actingAs($admin)
        ->get(route('conges.maternite.index'))
        ->assertOk();
});

it('refuse la suppression d\'un type de congé utilisé', function () {
    $admin = Utilisateur::where('role', Utilisateur::ROLE_SUPER_ADMIN)->first();
    $this->actingAs($admin);

    $type = TypeConge::where('code', 'CA')->first();

    // Le type est utilisé par les demandes créées par le seeder
    $this->delete(route('conges.types.destroy', $type))
        ->assertForbidden();
});