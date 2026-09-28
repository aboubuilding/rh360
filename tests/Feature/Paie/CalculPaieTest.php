<?php

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Paie\Models\BulletinPaie;
use App\Domain\Paie\Models\ElementPaieSalarie;
use App\Domain\Paie\Models\PeriodePaie;
use App\Domain\Paie\Models\RubriquePaie;
use App\Domain\Paie\Services\MoteurPaie;
use App\Domain\Personnel\Models\Salarie;

beforeEach(function () {
    $this->seed();
    $this->admin = Utilisateur::where('role', Utilisateur::ROLE_SUPER_ADMIN)->first();
    $this->actingAs($this->admin);
});

it('calcule un bulletin complet pour un salarié', function () {
    $salarie = Salarie::where('actif', true)->first();
    $rubrique = RubriquePaie::where('code', 'SAL-BASE')->first();

    // S'assurer qu'un élément fixe existe pour ce salarié
    ElementPaieSalarie::updateOrCreate(
        ['salarie_id' => $salarie->id, 'rubrique_id' => $rubrique->id],
        [
            'entreprise_id' => 1,
            'montant' => 250000,
            'actif' => true,
            'etat' => 1,
        ]
    );

    $periode = PeriodePaie::where('statut', 'open')->first()
        ?? PeriodePaie::factory()->create([
            'entreprise_id' => 1,
            'annee' => now()->year,
            'mois' => now()->month,
        ]);

    $moteur = app(MoteurPaie::class);
    $resultats = $moteur->calculer($periode);

    expect($resultats['bulletins_calcules'])->toBeGreaterThan(0);

    $bulletin = BulletinPaie::where('periode_id', $periode->id)
        ->where('salarie_id', $salarie->id)
        ->first();

    expect($bulletin)->not->toBeNull();
    expect((float) $bulletin->montant_brut)->toBeGreaterThan(0);
    expect((float) $bulletin->montant_net)->toBeGreaterThan(0);
    expect($bulletin->lignes()->count())->toBeGreaterThan(0);
});

it('refuse de calculer une période figée', function () {
    $periode = PeriodePaie::where('statut', 'validated')->first();

    expect(fn () => app(MoteurPaie::class)->calculer($periode))
        ->toThrow(\DomainException::class);
});

it('génère les cotisations CNSS et AMU', function () {
    $salarie = Salarie::where('actif', true)->first();
    $rubrique = RubriquePaie::where('code', 'SAL-BASE')->first();

    ElementPaieSalarie::updateOrCreate(
        ['salarie_id' => $salarie->id, 'rubrique_id' => $rubrique->id],
        ['entreprise_id' => 1, 'montant' => 300000, 'actif' => true, 'etat' => 1]
    );

    $periode = PeriodePaie::factory()->create([
        'entreprise_id' => 1,
        'annee' => now()->year,
        'mois' => 12, // Mois unique
    ]);

    app(MoteurPaie::class)->calculer($periode);

    $bulletin = BulletinPaie::where('periode_id', $periode->id)
        ->where('salarie_id', $salarie->id)
        ->first();

    $lignes = $bulletin->lignes()->pluck('code')->toArray();
    expect($lignes)->toContain('CNSS');
    expect($lignes)->toContain('AMU');
});