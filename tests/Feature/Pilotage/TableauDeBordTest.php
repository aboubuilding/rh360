<?php

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Pilotage\Services\AgregateurTableauDeBord;
use App\Domain\Pilotage\DTO\Widget;
use App\Domain\Pilotage\DTO\Indicateur;

beforeEach(function () {
    $this->seed();
    $this->admin = Utilisateur::where('role', Utilisateur::ROLE_SUPER_ADMIN)->first();
    $this->actingAs($this->admin);
});

it('affiche le tableau de bord', function () {
    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Tableau de bord');
});

it('affiche tous les widgets pour le super admin', function () {
    $agregateur = app(AgregateurTableauDeBord::class);
    $tdb = $agregateur->pourUtilisateur($this->admin);

    expect($tdb->widgets->count())->toBeGreaterThanOrEqual(7);

    $cles = $tdb->widgets->pluck('cle')->all();
    expect($cles)->toContain('personnel');
    expect($cles)->toContain('contrats');
    expect($cles)->toContain('carriere');
    expect($cles)->toContain('conges');
    expect($cles)->toContain('documents');
    expect($cles)->toContain('sst');
    expect($cles)->toContain('paie');
});

it('filtre les widgets selon les permissions de l\'utilisateur', function () {
    $auditeur = Utilisateur::create([
        'entreprise_id' => 1,
        'nom_complet' => 'Auditeur Test',
        'identifiant' => 'auditeur_' . uniqid(),
        'password' => bcrypt('x'),
        'role' => Utilisateur::ROLE_AUDITEUR,
        'actif' => true,
        'etat' => 1,
    ]);

    $agregateur = app(AgregateurTableauDeBord::class);
    $tdb = $agregateur->pourUtilisateur($auditeur);

    $cles = $tdb->widgets->pluck('cle')->all();

    // L'auditeur peut voir les contrats et la paie
    expect($cles)->toContain('contrats');
    expect($cles)->toContain('paie');

    // Mais pas le personnel nominatif
    expect($cles)->not->toContain('personnel');
    expect($cles)->not->toContain('carriere');
    expect($cles)->not->toContain('conges');
});

it('contient un contexte utilisateur complet', function () {
    $agregateur = app(AgregateurTableauDeBord::class);
    $tdb = $agregateur->pourUtilisateur($this->admin);

    expect($tdb->contexte)->toHaveKeys(['utilisateur', 'role', 'entreprise', 'date']);
    expect($tdb->contexte['utilisateur'])->toBe($this->admin->nom_complet);
});

it('retourne un indicateur structuré', function () {
    $indicateur = new Indicateur(
        cle: 'test',
        libelle: 'Test',
        valeur: 42,
        couleur: 'success',
        icone: 'fa-check',
        lien: 'https://example.com',
    );

    expect($indicateur->estZero())->toBeFalse();
    expect($indicateur->versArray())->toHaveKeys(['cle', 'libelle', 'valeur', 'couleur', 'icone', 'lien', 'est_zero']);
});

it('détecte les indicateurs à zéro', function () {
    $indicateur = new Indicateur(cle: 'test', libelle: 'Test', valeur: 0);
    expect($indicateur->estZero())->toBeTrue();
});

it('trie les widgets par ordre', function () {
    $agregateur = app(AgregateurTableauDeBord::class);
    $tdb = $agregateur->pourUtilisateur($this->admin);

    $ordres = $tdb->widgets->pluck('ordre')->all();
    $ordresTries = $ordres;
    sort($ordresTries);

    expect($ordres)->toBe($ordresTries);
});