<?php

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Personnel\Models\Salarie;
use App\Domain\Sst\Enums\CategorieEpi;
use App\Domain\Sst\Enums\NatureOperationEpi;
use App\Domain\Sst\Enums\StatutDotationEpi;
use App\Domain\Sst\Models\DotationEpi;

beforeEach(function () {
    $this->seed();
    $this->admin = Utilisateur::where('role', Utilisateur::ROLE_SUPER_ADMIN)->first();
    $this->actingAs($this->admin);
    $this->salarie = Salarie::first();
});

it('enregistre une nouvelle dotation EPI', function () {
    $this->post(route('sst.epi.store'), [
        'salarie_id' => $this->salarie->id,
        'categorie' => CategorieEpi::TETE->value,
        'intitule' => 'Casque de chantier',
        'quantite' => 2,
        'unite' => 'unité',
        'date_remise' => now()->format('Y-m-d'),
        'emetteur' => 'Service HSE',
    ])->assertRedirect();

    $this->assertDatabaseHas('dotations_epi', [
        'salarie_id' => $this->salarie->id,
        'intitule' => 'Casque de chantier',
        'statut' => StatutDotationEpi::REMIS->value,
    ]);
});

it('enregistre une opération de restitution', function () {
    $dotation = DotationEpi::factory()->create([
        'entreprise_id' => 1,
        'salarie_id' => $this->salarie->id,
        'statut' => StatutDotationEpi::EN_USAGE->value,
        'quantite' => 5,
    ]);

    $this->post(route('sst.epi.operations.store', $dotation), [
        'nature' => NatureOperationEpi::RESTITUTION->value,
        'date_evenement' => now()->format('Y-m-d'),
        'quantite' => 2,
        'resultat' => 'Restitution de 2 unités',
    ])->assertRedirect();

    // La quantité restante doit être recalculée
    expect($dotation->fresh()->quantiteRestante())->toBe(3);
});

it('change le statut après une opération de mise au rebut', function () {
    $dotation = DotationEpi::factory()->create([
        'entreprise_id' => 1,
        'statut' => StatutDotationEpi::A_REMPLACER->value,
    ]);

    $this->post(route('sst.epi.operations.store', $dotation), [
        'nature' => NatureOperationEpi::MISE_AU_REBUT->value,
        'date_evenement' => now()->format('Y-m-d'),
        'quantite' => 1,
    ])->assertRedirect();

    expect($dotation->fresh()->statut)->toBe(StatutDotationEpi::REBUTE);
});