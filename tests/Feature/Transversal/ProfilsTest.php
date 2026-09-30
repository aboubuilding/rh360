<?php

/*
| CDC §3 et §5 — Les sept profils et leurs périmètres par défaut, vérifiés sur
| la page d'accueil (index) de chaque sous-menu du §4.
*/

use App\Domain\Administration\Models\Utilisateur;

beforeEach(function () {
    $this->seed();
});

function accesProfil(string $role, string $route): \Illuminate\Testing\TestResponse
{
    $utilisateur = Utilisateur::factory()->role($role)->create();

    return test()->actingAs($utilisateur)->get(route($route));
}

it('ouvre les écrans prévus pour le profil', function (string $role, string $route) {
    accesProfil($role, $route)->assertOk();
})->with([
    // Administrateur : entreprise, comptes, organisation, grille salariale, audit
    'Admin — entreprise' => [Utilisateur::ROLE_ADMIN, 'admin.entreprise.index'],
    'Admin — utilisateurs' => [Utilisateur::ROLE_ADMIN, 'admin.utilisateurs.index'],
    'Admin — structures' => [Utilisateur::ROLE_ADMIN, 'organisation.structures.index'],
    'Admin — grille' => [Utilisateur::ROLE_ADMIN, 'classification.referentiels.index'],
    'Admin — audit' => [Utilisateur::ROLE_ADMIN, 'admin.audit.index'],
    // DRH : pilotage RH complet et permissions
    'DRH — contrats' => [Utilisateur::ROLE_DRH, 'contrats.contrats.index'],
    'DRH — carrière' => [Utilisateur::ROLE_DRH, 'carriere.mouvements.index'],
    'DRH — congés' => [Utilisateur::ROLE_DRH, 'conges.demandes.index'],
    'DRH — paie' => [Utilisateur::ROLE_DRH, 'paie.periodes.index'],
    'DRH — visites médicales' => [Utilisateur::ROLE_DRH, 'sst.visites.index'],
    'DRH — permissions' => [Utilisateur::ROLE_DRH, 'admin.permissions.matrice'],
    // Responsable RH : gestion quotidienne
    'RH — salariés' => [Utilisateur::ROLE_RH, 'personnel.salaries.index'],
    'RH — contrats' => [Utilisateur::ROLE_RH, 'contrats.contrats.index'],
    'RH — carrière' => [Utilisateur::ROLE_RH, 'carriere.mouvements.index'],
    'RH — absences' => [Utilisateur::ROLE_RH, 'conges.absences.index'],
    'RH — formation' => [Utilisateur::ROLE_RH, 'formation.formations.index'],
    'RH — recrutement' => [Utilisateur::ROLE_RH, 'recrutement.besoins.index'],
    'RH — paie' => [Utilisateur::ROLE_RH, 'paie.periodes.index'],
    'RH — risques' => [Utilisateur::ROLE_RH, 'sst.risques.index'],
    // Manager : organisation, congés, formation, recrutement, performance
    'Manager — organisation' => [Utilisateur::ROLE_MANAGER, 'organisation.structures.index'],
    'Manager — congés' => [Utilisateur::ROLE_MANAGER, 'conges.demandes.index'],
    'Manager — formation' => [Utilisateur::ROLE_MANAGER, 'formation.formations.index'],
    'Manager — recrutement' => [Utilisateur::ROLE_MANAGER, 'recrutement.besoins.index'],
    'Manager — entretiens' => [Utilisateur::ROLE_MANAGER, 'performance.entretiens.index'],
    // Direction générale : consultation et indicateurs agrégés
    'Direction — salariés' => [Utilisateur::ROLE_DIRECTION, 'personnel.salaries.index'],
    'Direction — contrats' => [Utilisateur::ROLE_DIRECTION, 'contrats.contrats.index'],
    'Direction — paie' => [Utilisateur::ROLE_DIRECTION, 'paie.periodes.index'],
    'Direction — reporting SST' => [Utilisateur::ROLE_DIRECTION, 'sst.reporting.index'],
    // Auditeur : lecture seule paie, contrats, audit, indicateurs agrégés
    'Auditeur — paie' => [Utilisateur::ROLE_AUDITEUR, 'paie.periodes.index'],
    'Auditeur — contrats' => [Utilisateur::ROLE_AUDITEUR, 'contrats.contrats.index'],
    'Auditeur — audit' => [Utilisateur::ROLE_AUDITEUR, 'admin.audit.index'],
    'Auditeur — reporting SST' => [Utilisateur::ROLE_AUDITEUR, 'sst.reporting.index'],
]);

it('ferme les écrans hors du périmètre du profil', function (string $role, string $route) {
    accesProfil($role, $route)->assertForbidden();
})->with([
    // Administrateur : aucun accès aux données nominatives des salariés
    'Admin — salariés' => [Utilisateur::ROLE_ADMIN, 'personnel.salaries.index'],
    'Admin — contrats' => [Utilisateur::ROLE_ADMIN, 'contrats.contrats.index'],
    'Admin — paie' => [Utilisateur::ROLE_ADMIN, 'paie.periodes.index'],
    'Admin — visites médicales' => [Utilisateur::ROLE_ADMIN, 'sst.visites.index'],
    'Admin — permissions' => [Utilisateur::ROLE_ADMIN, 'admin.permissions.matrice'],
    // Responsable RH : ni comptes ni audit
    'RH — utilisateurs' => [Utilisateur::ROLE_RH, 'admin.utilisateurs.index'],
    'RH — audit' => [Utilisateur::ROLE_RH, 'admin.audit.index'],
    // Manager : ni paie ni contrats ni santé
    'Manager — paie' => [Utilisateur::ROLE_MANAGER, 'paie.periodes.index'],
    'Manager — contrats' => [Utilisateur::ROLE_MANAGER, 'contrats.contrats.index'],
    'Manager — visites médicales' => [Utilisateur::ROLE_MANAGER, 'sst.visites.index'],
    // Direction : SST uniquement en agrégé, jamais les registres nominatifs
    'Direction — visites médicales' => [Utilisateur::ROLE_DIRECTION, 'sst.visites.index'],
    'Direction — accidents' => [Utilisateur::ROLE_DIRECTION, 'sst.evenements.index'],
    'Direction — risques' => [Utilisateur::ROLE_DIRECTION, 'sst.risques.index'],
    'Direction — EPI' => [Utilisateur::ROLE_DIRECTION, 'sst.epi.index'],
    'Direction — habilitations' => [Utilisateur::ROLE_DIRECTION, 'sst.habilitations.index'],
    // Auditeur : lecture seule, pas de dossiers nominatifs
    'Auditeur — salariés' => [Utilisateur::ROLE_AUDITEUR, 'personnel.salaries.index'],
    'Auditeur — carrière' => [Utilisateur::ROLE_AUDITEUR, 'carriere.mouvements.index'],
    'Auditeur — congés' => [Utilisateur::ROLE_AUDITEUR, 'conges.demandes.index'],
    'Auditeur — visites médicales' => [Utilisateur::ROLE_AUDITEUR, 'sst.visites.index'],
]);

it('réserve la validation aux profils de décision', function () {
    $drh = Utilisateur::factory()->role(Utilisateur::ROLE_DRH)->create();
    $rh = Utilisateur::factory()->role(Utilisateur::ROLE_RH)->create();

    foreach (['contrats.validate', 'carriere.validate', 'conges.validate', 'paie.valider'] as $permission) {
        expect($drh->peut($permission))->toBeTrue("DRH : {$permission}");
        expect($rh->peut($permission))->toBeFalse("RH : {$permission}");
    }
});

it('donne à chaque profil l\'accès au tableau de bord', function (string $role) {
    accesProfil($role, 'dashboard')->assertOk();
})->with([
    Utilisateur::ROLE_ADMIN, Utilisateur::ROLE_DRH, Utilisateur::ROLE_RH,
    Utilisateur::ROLE_MANAGER, Utilisateur::ROLE_DIRECTION, Utilisateur::ROLE_AUDITEUR,
]);
