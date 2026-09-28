<?php

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Carriere\Enums\StatutMouvement;
use App\Domain\Carriere\Enums\TypeMouvement;
use App\Domain\Carriere\Models\InstantaneCarriere;
use App\Domain\Carriere\Models\MouvementCarriere;
use App\Domain\Carriere\Models\SituationCarriere;
use App\Domain\Carriere\Services\AppliqueurMouvement;
use App\Domain\Classification\Models\PositionClassification;
use App\Domain\Organisation\Models\Poste;
use App\Domain\Organisation\Models\Structure;
use App\Domain\Personnel\Models\Affectation;
use App\Domain\Personnel\Models\Salarie;

beforeEach(function () {
    $this->seed();
    $this->admin = Utilisateur::where('role', Utilisateur::ROLE_SUPER_ADMIN)->first();
    $this->actingAs($this->admin);

    $this->salarie = Salarie::first();
    $this->poste = Poste::first();
    $this->structure = Structure::first();
    $this->position = PositionClassification::first();
});

it('applique un mouvement programmé à la date d\'effet', function () {
    $m = MouvementCarriere::factory()->create([
        'entreprise_id' => 1,
        'salarie_id' => $this->salarie->id,
        'type_mouvement' => TypeMouvement::AFFECTATION->value,
        'statut' => StatutMouvement::PROGRAMME->value,
        'date_effet' => now()->subDays(1),
        'structure_cible_id' => $this->structure->id,
        'poste_cible_id' => $this->poste->id,
        'position_classification_cible_id' => $this->position->id,
    ]);

    app(AppliqueurMouvement::class)->appliquer($m);

    expect($m->fresh()->statut)->toBe(StatutMouvement::EFFECTIF);

    // Une nouvelle affectation en cours doit exister
    $aff = Affectation::where('salarie_id', $this->salarie->id)
        ->where('en_cours', true)
        ->latest('id')
        ->first();
    expect($aff->poste_id)->toBe($this->poste->id);
});

it('crée un instantané avant/après', function () {
    $m = MouvementCarriere::factory()->create([
        'entreprise_id' => 1,
        'salarie_id' => $this->salarie->id,
        'type_mouvement' => TypeMouvement::AFFECTATION->value,
        'statut' => StatutMouvement::PROGRAMME->value,
        'date_effet' => now()->subDays(1),
        'structure_cible_id' => $this->structure->id,
        'poste_cible_id' => $this->poste->id,
    ]);

    app(AppliqueurMouvement::class)->appliquer($m);

    $inst = InstantaneCarriere::where('mouvement_id', $m->id)->first();
    expect($inst)->not->toBeNull();
    expect($inst->details)->toHaveKeys(['avant', 'apres', 'nature']);
});

it('refuse d\'appliquer un mouvement non programmé', function () {
    $m = MouvementCarriere::factory()->create([
        'entreprise_id' => 1,
        'salarie_id' => $this->salarie->id,
        'statut' => StatutMouvement::VALIDE->value,
        'date_effet' => now()->addDays(10),
    ]);

    expect(fn () => app(AppliqueurMouvement::class)->appliquer($m))
        ->toThrow(\DomainException::class);
});

it('le balayage applique les mouvements programmés échus', function () {
    MouvementCarriere::factory()->create([
        'entreprise_id' => 1,
        'salarie_id' => $this->salarie->id,
        'statut' => StatutMouvement::PROGRAMME->value,
        'date_effet' => now()->subDays(2),
    ]);

    $count = app(AppliqueurMouvement::class)->balayer(1);
    expect($count)->toBeGreaterThanOrEqual(1);
});