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
    $m = MouvementCarriere::factory()->create([
        'entreprise_id' => 1,
        'salarie_id' => $this->salarie->id,
        'statut' => StatutMouvement::PROGRAMME->value,
        'date_effet' => now()->subDays(2),
    ]);

    $count = app(AppliqueurMouvement::class)->balayer(1);
    expect($count)->toBeGreaterThanOrEqual(1);
    expect($m->fresh()->statut)->toBe(StatutMouvement::EFFECTIF);
});

it('le balayage ignore les mouvements programmés à date future', function () {
    $m = MouvementCarriere::factory()->create([
        'entreprise_id' => 1,
        'salarie_id' => $this->salarie->id,
        'statut' => StatutMouvement::PROGRAMME->value,
        'date_effet' => now()->addDays(5),
    ]);

    app(AppliqueurMouvement::class)->balayer(1);

    expect($m->fresh()->statut)->toBe(StatutMouvement::PROGRAMME);
});

it('reprend la structure courante quand l\'acte ne change que le poste', function () {
    $courante = Affectation::where('salarie_id', $this->salarie->id)->where('en_cours', true)->first();
    $autrePoste = Poste::where('id', '!=', $courante->poste_id)->first();

    $m = MouvementCarriere::factory()->create([
        'entreprise_id' => 1,
        'salarie_id' => $this->salarie->id,
        'type_mouvement' => TypeMouvement::MUTATION->value,
        'statut' => StatutMouvement::PROGRAMME->value,
        'date_effet' => now()->subDay(),
        'poste_cible_id' => $autrePoste->id,
    ]);

    app(AppliqueurMouvement::class)->appliquer($m);

    $nouvelle = Affectation::where('salarie_id', $this->salarie->id)->where('en_cours', true)->sole();
    expect($nouvelle->poste_id)->toBe($autrePoste->id);
    expect($nouvelle->structure_id)->toBe($courante->structure_id);
    expect($courante->fresh()->en_cours)->toBeFalse();
});

it('ne modifie pas le poste permanent lors d\'un intérim', function () {
    $courante = Affectation::where('salarie_id', $this->salarie->id)->where('en_cours', true)->first();
    $autrePoste = Poste::where('id', '!=', $courante->poste_id)->first();

    $m = MouvementCarriere::factory()->create([
        'entreprise_id' => 1,
        'salarie_id' => $this->salarie->id,
        'type_mouvement' => TypeMouvement::INTERIM->value,
        'statut' => StatutMouvement::PROGRAMME->value,
        'date_effet' => now()->subDay(),
        'poste_cible_id' => $autrePoste->id,
    ]);

    app(AppliqueurMouvement::class)->appliquer($m);

    expect($m->fresh()->statut)->toBe(StatutMouvement::EFFECTIF);
    $toujours = Affectation::where('salarie_id', $this->salarie->id)->where('en_cours', true)->sole();
    expect($toujours->id)->toBe($courante->id);
    expect($toujours->poste_id)->toBe($courante->poste_id);
});

it('un avancement d\'échelon conserve les dates d\'effet de catégorie et de classe', function () {
    $depart = PositionClassification::whereNotNull('position_suivante_id')->orderBy('ordre')->first();
    $cible = PositionClassification::find($depart->position_suivante_id);
    expect($cible->categorie_id)->toBe($depart->categorie_id);
    expect($cible->classe_id)->toBe($depart->classe_id);

    $situation = SituationCarriere::where('salarie_id', $this->salarie->id)->first();
    $situation->update([
        'position_classification_id' => $depart->id,
        'date_effet_categorie' => '2018-01-01',
        'date_effet_classe' => '2020-01-01',
        'date_effet_echelon' => '2022-01-01',
        'date_reference_avancement' => '2022-01-01',
    ]);

    $m = MouvementCarriere::factory()->create([
        'entreprise_id' => 1,
        'salarie_id' => $this->salarie->id,
        'type_mouvement' => TypeMouvement::AVANCEMENT->value,
        'statut' => StatutMouvement::PROGRAMME->value,
        'date_effet' => now()->subDay()->startOfDay(),
        'position_classification_depart_id' => $depart->id,
        'position_classification_cible_id' => $cible->id,
    ]);

    app(AppliqueurMouvement::class)->appliquer($m);

    $s = $situation->fresh();
    expect($s->position_classification_id)->toBe($cible->id);
    expect($s->date_effet_categorie->format('Y-m-d'))->toBe('2018-01-01');
    expect($s->date_effet_classe->format('Y-m-d'))->toBe('2020-01-01');
    expect($s->date_effet_echelon->format('Y-m-d'))->toBe(now()->subDay()->format('Y-m-d'));
    expect($s->date_reference_avancement->format('Y-m-d'))->toBe(now()->subDay()->format('Y-m-d'));
});

it('un acte sans position cible ne modifie pas la classification', function () {
    $situation = SituationCarriere::where('salarie_id', $this->salarie->id)->first();
    $positionAvant = $situation->position_classification_id;

    $m = MouvementCarriere::factory()->create([
        'entreprise_id' => 1,
        'salarie_id' => $this->salarie->id,
        'type_mouvement' => TypeMouvement::PROMOTION->value,
        'statut' => StatutMouvement::PROGRAMME->value,
        'date_effet' => now()->subDay(),
    ]);

    app(AppliqueurMouvement::class)->appliquer($m);

    expect($situation->fresh()->position_classification_id)->toBe($positionAvant);
});