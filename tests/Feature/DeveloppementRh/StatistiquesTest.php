<?php

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\DeveloppementRh\Services\GenerateurStatistiquesDeveloppementRh;

beforeEach(function () {
    $this->seed();
    $this->admin = Utilisateur::where('role', Utilisateur::ROLE_SUPER_ADMIN)->first();
    $this->actingAs($this->admin);
});

it('génère les statistiques globales Développement RH', function () {
    $stats = app(GenerateurStatistiquesDeveloppementRh::class)
        ->globales(1, now()->startOfYear(), now()->endOfYear());

    expect($stats)->toHaveKeys(['formation', 'performance', 'recrutement']);
    expect($stats['formation'])->toHaveKeys(['sessions_programmees', 'budget_engage', 'heures_formation', 'besoins_en_attente']);
    expect($stats['performance'])->toHaveKeys(['campagnes_actives', 'campagnes_cloturees', 'entretiens_a_realiser']);
    expect($stats['recrutement'])->toHaveKeys(['besoins_actifs', 'besoins_pourvus', 'candidats_en_cours']);
});