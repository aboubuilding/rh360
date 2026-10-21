<?php

/*
| Garde-fous des avancements (CCIT art. 12, 29, 33 ; Code art. 71, 119) sur le barème 2026 :
| échelon et catégorie immédiatement supérieurs, aucune baisse, changement de classe motivé,
| fenêtre de catégorie, notification préalable, salaire au minimum, historique immuable,
| propositions d'avancement de catégorie et de hors catégorie.
*/

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Carriere\Enums\StatutMouvement;
use App\Domain\Carriere\Enums\TypeMouvement;
use App\Domain\Carriere\Models\InstantaneCarriere;
use App\Domain\Carriere\Models\MouvementCarriere;
use App\Domain\Carriere\Models\ParametreAvancement;
use App\Domain\Carriere\Models\SituationCarriere;
use App\Domain\Carriere\Services\AppliqueurMouvement;
use App\Domain\Carriere\Services\ControleAvancement;
use App\Domain\Carriere\Services\PropositionsAvancementCategorie;
use App\Domain\Classification\Models\PositionClassification;
use App\Domain\Classification\Models\ReferentielClassification;
use App\Domain\Classification\Services\InstallateurBaremeCcit2026;
use App\Domain\Paie\Actions\AffecterElementFixe;
use App\Domain\Paie\Models\ElementPaieSalarie;
use App\Domain\Paie\Models\RubriquePaie;
use App\Domain\Personnel\Models\Salarie;

beforeEach(function () {
    $this->seed();
    $this->actingAs(Utilisateur::where('role', Utilisateur::ROLE_SUPER_ADMIN)->first());
    app(InstallateurBaremeCcit2026::class)->installer(1);
    $referentiel = ReferentielClassification::where('code', InstallateurBaremeCcit2026::CODE_REFERENTIEL)->sole();
    $this->position = fn (string $code) => PositionClassification::where('referentiel_id', $referentiel->id)->where('code', $code)->sole();

    $this->salarie = Salarie::orderBy('id')->firstOrFail();
    $this->placer = fn (string $code, string $depuis = '2023-01-01') => SituationCarriere::where('salarie_id', $this->salarie->id)
        ->update(['position_classification_id' => ($this->position)($code)->id, 'date_effet_echelon' => $depuis, 'date_reference_avancement' => $depuis, 'date_effet_categorie' => $depuis]);
    $this->salaire = fn (?int $montant = null) => $montant === null
        ? (int) ElementPaieSalarie::where('salarie_id', $this->salarie->id)->whereHas('rubrique', fn ($q) => $q->where('code', 'SAL-BASE'))->value('montant')
        : ElementPaieSalarie::where('salarie_id', $this->salarie->id)->whereHas('rubrique', fn ($q) => $q->where('code', 'SAL-BASE'))->update(['montant' => $montant]);
});

function appliquerActe(string $type, string $cible, array $attributs = []): MouvementCarriere
{
    $mouvement = MouvementCarriere::factory()->create(array_merge([
        'entreprise_id' => 1,
        'salarie_id' => test()->salarie->id,
        'type_mouvement' => $type,
        'statut' => StatutMouvement::PROGRAMME->value,
        'date_effet' => today()->subDay(),
        'position_classification_cible_id' => (test()->position)($cible)->id,
        'motif' => 'Acte de test',
    ], $attributs));

    return app(AppliqueurMouvement::class)->appliquer($mouvement);
}

function positionActuelle(): string
{
    return SituationCarriere::where('salarie_id', test()->salarie->id)->first()->positionClassification->code;
}

// --- Échelon ---

it('refuse un saut d\'échelon et accepte l\'échelon immédiatement supérieur', function () {
    ($this->placer)('E4-A');

    expect(fn () => appliquerActe(TypeMouvement::AVANCEMENT->value, 'E4-C'))->toThrow(DomainException::class, 'sans saut');
    expect(positionActuelle())->toBe('E4-A');

    appliquerActe(TypeMouvement::AVANCEMENT->value, 'E4-B');
    expect(positionActuelle())->toBe('E4-B');
});

it('exige un motif pour un avancement d\'échelon au mérite', function () {
    ($this->placer)('E4-A');

    expect(fn () => appliquerActe(TypeMouvement::AVANCEMENT_ANTICIPE->value, 'E4-B', ['motif' => null]))->toThrow(DomainException::class, 'motivé');
    appliquerActe(TypeMouvement::AVANCEMENT_ANTICIPE->value, 'E4-B', ['motif' => 'Évaluation exceptionnelle 2026']);
    expect(positionActuelle())->toBe('E4-B');
});

it('aligne le salaire de base sur le minimum de la nouvelle position, sans jamais le baisser', function () {
    ($this->placer)('E4-A');
    ($this->salaire)(72000);
    appliquerActe(TypeMouvement::AVANCEMENT->value, 'E4-B');
    expect(($this->salaire)())->toBe(75500);

    ($this->salaire)(90000);
    appliquerActe(TypeMouvement::AVANCEMENT->value, 'E4-C');
    expect(($this->salaire)())->toBe(90000);
});

// --- Catégorie, classe, baisse ---

it('refuse un saut de catégorie et accepte la catégorie immédiatement supérieure', function () {
    ($this->placer)('E4-C');

    expect(fn () => appliquerActe(TypeMouvement::PROMOTION->value, 'E6-A'))->toThrow(DomainException::class, 'immédiatement supérieure (E5)');
    appliquerActe(TypeMouvement::PROMOTION->value, 'E5-A');
    expect(positionActuelle())->toBe('E5-A');
});

it('refuse un avancement de catégorie avant l\'ouverture de la fenêtre', function () {
    ($this->placer)('E4-C', today()->subYear()->toDateString());

    expect(fn () => appliquerActe(TypeMouvement::PROMOTION->value, 'E5-A', ['sous_type_mouvement' => ControleAvancement::AVANCEMENT_CATEGORIE]))
        ->toThrow(DomainException::class, 'la fenêtre ne s\'ouvre que le');
});

it('n\'autorise un changement de classe que pour l\'un des trois motifs', function () {
    ($this->placer)('E6-C');

    expect(fn () => appliquerActe(TypeMouvement::PROMOTION->value, 'M1-A'))->toThrow(DomainException::class, 'Changement de classe refusé');
    appliquerActe(TypeMouvement::PROMOTION->value, 'M1-A', ['sous_type_mouvement' => 'qualification_superieure']);
    expect(positionActuelle())->toBe('M1-A');
});

it('refuse une baisse de catégorie, sauf réintégration après essai ou rétrogradation disciplinaire documentée', function () {
    ($this->placer)('M2-A');

    expect(fn () => appliquerActe(TypeMouvement::RECLASSEMENT->value, 'M1-A'))->toThrow(DomainException::class, 'baisse de catégorie');
    expect(fn () => appliquerActe(TypeMouvement::RECLASSEMENT->value, 'M1-A', ['sous_type_mouvement' => ControleAvancement::RETROGRADATION_DISCIPLINAIRE]))
        ->toThrow(DomainException::class, 'baisse de catégorie');

    appliquerActe(TypeMouvement::RECLASSEMENT->value, 'M1-A', ['sous_type_mouvement' => ControleAvancement::REINTEGRATION_ESSAI]);
    expect(positionActuelle())->toBe('M1-A');

    ($this->placer)('M2-A');
    appliquerActe(TypeMouvement::RECLASSEMENT->value, 'M1-A', ['sous_type_mouvement' => ControleAvancement::RETROGRADATION_DISCIPLINAIRE, 'reference_acte' => 'DISC-2026-004']);
    expect(positionActuelle())->toBe('M1-A');
});

it('remet à zéro l\'ancienneté d\'échelon après un changement de catégorie', function () {
    ($this->placer)('E4-C', '2023-01-01');
    appliquerActe(TypeMouvement::PROMOTION->value, 'E5-A');

    expect(SituationCarriere::where('salarie_id', $this->salarie->id)->first()->date_effet_echelon->toDateString())->toBe(today()->subDay()->toDateString());
});

// --- Notification et historique ---

it('refuse la prise d\'effet d\'un acte non notifié, sauf si l\'entreprise a désactivé ce contrôle', function () {
    ($this->placer)('E4-A');

    expect(fn () => appliquerActe(TypeMouvement::AVANCEMENT->value, 'E4-B', ['date_notification' => null]))->toThrow(DomainException::class, 'notifié');

    ParametreAvancement::create(['entreprise_id' => 1, 'valeurs' => ['exiger_notification_prealable' => false]]);
    app()->forgetScopedInstances();
    appliquerActe(TypeMouvement::AVANCEMENT->value, 'E4-B', ['date_notification' => null]);
    expect(positionActuelle())->toBe('E4-B');
});

it('conserve un historique de carrière immuable', function () {
    ($this->placer)('E4-A');
    $mouvement = appliquerActe(TypeMouvement::AVANCEMENT->value, 'E4-B');
    $instantane = InstantaneCarriere::where('mouvement_id', $mouvement->id)->sole();

    expect(fn () => $instantane->update(['details' => []]))->toThrow(DomainException::class, 'ne peut pas être modifié');
    expect(fn () => $instantane->delete())->toThrow(DomainException::class, 'ne peut pas être supprimé');
});

// --- Propositions de catégorie et hors catégorie ---

it('propose l\'avancement de catégorie à l\'échelon A lorsque la fenêtre est ouverte', function () {
    ($this->placer)('E4-C', '2023-01-01');

    app(PropositionsAvancementCategorie::class)->preparer(1);

    $proposition = MouvementCarriere::where('salarie_id', $this->salarie->id)->where('type_mouvement', TypeMouvement::PROMOTION->value)->sole();
    expect($proposition->position_classification_cible_id)->toBe(($this->position)('E5-A')->id);
    expect($proposition->sous_type_mouvement)->toBe(ControleAvancement::AVANCEMENT_CATEGORIE);
    expect($proposition->statut)->toBe(StatutMouvement::BROUILLON);

    // Pas de doublon tant que la proposition est en cours.
    expect(app(PropositionsAvancementCategorie::class)->preparer(1))->toBe(0);
    expect(MouvementCarriere::where('salarie_id', $this->salarie->id)->where('type_mouvement', TypeMouvement::PROMOTION->value)->count())->toBe(1);
});

it('propose le premier échelon au moins égal au salaire actuel si le paramètre le prévoit', function () {
    ($this->placer)('E4-C', '2023-01-01');
    ($this->salaire)(86000);
    ParametreAvancement::create(['entreprise_id' => 1, 'valeurs' => ['echelon_arrivee' => 'salaire_immediatement_superieur']]);
    app()->forgetScopedInstances();

    app(PropositionsAvancementCategorie::class)->preparer(1);

    // E5-A 80 500 et E5-B 84 500 < 86 000 : E5-C (88 500)
    expect(MouvementCarriere::where('salarie_id', $this->salarie->id)->where('type_mouvement', TypeMouvement::PROMOTION->value)->value('position_classification_cible_id'))
        ->toBe(($this->position)('E5-C')->id);
});

it('propose l\'activation du hors catégorie après le dernier échelon de E6', function () {
    ($this->placer)('E6-C', '2023-01-01');

    app(PropositionsAvancementCategorie::class)->preparer(1);

    $proposition = MouvementCarriere::where('salarie_id', $this->salarie->id)->where('type_mouvement', TypeMouvement::PROMOTION->value)->sole();
    expect($proposition->position_classification_cible_id)->toBe(($this->position)('HC-E-HC')->id);
    expect($proposition->motif)->toContain('hors catégorie');
});

it('prépare depuis l\'écran les propositions d\'échelon et de catégorie', function () {
    ($this->placer)('E4-C', '2023-01-01');

    $this->postJson(route('carriere.avancements.preparer'))->assertOk()->assertJsonPath('message', fn ($m) => str_contains($m, 'proposition'));

    expect(MouvementCarriere::where('salarie_id', $this->salarie->id)->where('type_mouvement', TypeMouvement::PROMOTION->value)->count())->toBe(1);
});

// --- Barème ---

it('refuse un salaire de base inférieur au minimum de la position', function () {
    ($this->placer)('E3-B');
    $rubrique = RubriquePaie::where('code', 'SAL-BASE')->firstOrFail();

    expect(fn () => app(AffecterElementFixe::class)->executer($this->salarie->id, $rubrique->id, 66000))
        ->toThrow(DomainException::class, 'minimum de la position E3-B (67 500 FCFA)');
    expect(app(AffecterElementFixe::class)->executer($this->salarie->id, $rubrique->id, 67500)->montant)->toEqual(67500);
});
