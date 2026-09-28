<?php

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Personnel\Models\Salarie;

beforeEach(function () {
    $this->seed();
    $this->admin = Utilisateur::where('role', Utilisateur::ROLE_SUPER_ADMIN)->first();
    $this->actingAs($this->admin);
});

it('recherche un salarié par nom', function () {
    $salarie = Salarie::first();

    $this->getJson(route('recherche.salaries', ['q' => mb_substr($salarie->nom, 0, 3)]))
        ->assertOk()
        ->assertJsonStructure([
            '*' => ['id', 'matricule', 'nom_complet', 'initiales', 'poste', 'structure', 'url'],
        ]);
});

it('recherche un salarié par matricule', function () {
    $salarie = Salarie::first();

    $response = $this->getJson(route('recherche.salaries', ['q' => $salarie->matricule]))
        ->assertOk();

    $data = $response->json();
    expect(collect($data)->pluck('matricule'))->toContain($salarie->matricule);
});

it('retourne un tableau vide si le terme est trop court', function () {
    $response = $this->getJson(route('recherche.salaries', ['q' => 'a']))
        ->assertOk();

    expect($response->json())->toBe([]);
});

it('limite les résultats à 10', function () {
    $response = $this->getJson(route('recherche.salaries', ['q' => 'A']))
        ->assertOk();

    expect(count($response->json()))->toBeLessThanOrEqual(10);
});

it('retourne l\'aperçu rapide d\'un salarié', function () {
    $salarie = Salarie::first();

    $this->getJson(route('recherche.apercu', $salarie))
        ->assertOk()
        ->assertJsonStructure([
            'id', 'matricule', 'nom_complet', 'initiales',
            'date_embauche', 'anciennete', 'poste', 'structure',
            'statut_emploi', 'statut_dossier', 'fiche_url',
        ]);
});

it('refuse l\'aperçu d\'un salarié d\'une autre entreprise', function () {
    $autreEntreprise = \App\Domain\Administration\Models\Entreprise::create([
        'nom' => 'Autre SARL',
        'pays' => 'Togo',
        'devise' => 'XOF',
        'etat' => 1,
    ]);

    $salarieAutre = Salarie::create([
        'entreprise_id' => $autreEntreprise->id,
        'matricule' => 'AUTRE-001',
        'nom' => 'Test',
        'prenoms' => 'Autre',
        'etat' => 1,
    ]);

    $this->getJson(route('recherche.apercu', $salarieAutre))
        ->assertNotFound();
});

it('refuse la recherche sans permission salaries.view', function () {
    $utilisateur = Utilisateur::create([
        'entreprise_id' => 1,
        'nom_complet' => 'Sans Perm',
        'identifiant' => 'sans_perm_' . uniqid(),
        'password' => bcrypt('x'),
        'role' => Utilisateur::ROLE_ADMIN,
        'actif' => true,
        'etat' => 1,
    ]);

    $this->actingAs($utilisateur)
        ->getJson(route('recherche.salaries', ['q' => 'Dupont']))
        ->assertForbidden();
});