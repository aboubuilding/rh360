<?php

/*
| Barème 2026 (Annexes I et II) installé dans la classification, et son utilisation par
| l'indemnité d'intérim, la prime d'ancienneté et la fenêtre d'avancement de catégorie.
*/

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Carriere\Models\SituationCarriere;
use App\Domain\Carriere\Services\CalculateurEligibilite;
use App\Domain\Classification\Models\CategorieClassification;
use App\Domain\Classification\Models\PositionClassification;
use App\Domain\Classification\Models\ReferentielClassification;
use App\Domain\Classification\Services\InstallateurBaremeCcit2026;
use App\Domain\Interim\Services\RepertoireBareme;
use App\Domain\Paie\Models\RegleAnciennete;
use App\Domain\Paie\Services\CalculateurAnciennete;
use App\Domain\Personnel\Models\Salarie;

beforeEach(function () {
    $this->seed();
    $this->actingAs(Utilisateur::where('role', Utilisateur::ROLE_SUPER_ADMIN)->first());
    $this->artisan('classification:installer-bareme-2026', ['--entreprise' => 1])->assertSuccessful();
    $this->referentiel = ReferentielClassification::where('code', InstallateurBaremeCcit2026::CODE_REFERENTIEL)->sole();
    $this->position = fn (string $code) => PositionClassification::where('referentiel_id', $this->referentiel->id)->where('code', $code)->sole();
});

it('installe les trois classes, les catégories, le hors catégorie de chaque classe et les montants de l\'Annexe II', function () {
    $categories = CategorieClassification::where('referentiel_id', $this->referentiel->id)->orderBy('ordre')->pluck('code')->all();

    expect($categories)->toBe(['E1', 'E2', 'E3', 'E4', 'E5', 'E6', 'HC-E', 'M1', 'M2', 'M3', 'M4', 'HC-M', 'C1', 'C2', 'C3', 'C4', 'HC-C']);
    // 1 position pour E1, 3 pour chacune des 13 autres catégories, 1 par hors catégorie.
    expect(PositionClassification::where('referentiel_id', $this->referentiel->id)->count())->toBe(43);
    expect(($this->position)('E1-U')->salaire_minimum)->toBe(52500);
    expect(($this->position)('E4-A')->salaire_minimum)->toBe(71500);
    expect(($this->position)('M1-A')->salaire_minimum)->toBe(103500);
    expect(($this->position)('C4-C')->salaire_minimum)->toBe(246500);
    expect(($this->position)('HC-E-HC')->salaire_minimum)->toBeNull();
    expect($this->referentiel->niveau_source)->toBe('ccit');
});

it('chaîne les échelons A → B → C dans chaque catégorie, sans échelon après C ni après E1', function () {
    expect(($this->position)('E4-A')->position_suivante_id)->toBe(($this->position)('E4-B')->id);
    expect(($this->position)('E4-B')->position_suivante_id)->toBe(($this->position)('E4-C')->id);
    expect(($this->position)('E4-C')->position_suivante_id)->toBeNull();
    expect(($this->position)('E1-U')->position_suivante_id)->toBeNull();
});

it('est idempotent', function () {
    $this->artisan('classification:installer-bareme-2026', ['--entreprise' => 1])->assertSuccessful();

    expect(ReferentielClassification::where('code', InstallateurBaremeCcit2026::CODE_REFERENTIEL)->count())->toBe(1);
    expect(PositionClassification::where('referentiel_id', $this->referentiel->id)->count())->toBe(43);
});

it('fournit le minimum de la catégorie à l\'indemnité d\'intérim : E4-A vers M1 = 32 000 FCFA', function () {
    $m1 = CategorieClassification::where('referentiel_id', $this->referentiel->id)->where('code', 'M1')->sole();

    expect(app(RepertoireBareme::class)->salaireMinimum($m1, now()))->toBe(103500);
    expect(app(RepertoireBareme::class)->salaireMinimum($m1, now()) - ($this->position)('E4-A')->salaire_minimum)->toBe(32000);
});

it('calcule la prime d\'ancienneté sur le minimum de la catégorie', function () {
    $salarie = Salarie::orderBy('id')->firstOrFail();
    $salarie->update(['date_embauche' => now()->subYears(10)]);
    SituationCarriere::where('salarie_id', $salarie->id)->update(['position_classification_id' => ($this->position)('M2-C')->id]);
    RegleAnciennete::query()->update(['mode_base' => 'echelon_a', 'mode_taux' => 'ccit_cumul', 'taux_initial' => 2, 'taux_max' => 35, 'annee_debut_increment' => 4]);

    // M2-A = 115 000 ; 10 ans : 9 % → 10 350 FCFA
    expect(app(CalculateurAnciennete::class)->calculerPrime($salarie, 125000, now()))->toBe(10350.0);
});

it('propose la catégorie suivante dans la classe, et le hors catégorie après E6', function () {
    $salarie = Salarie::orderBy('id')->firstOrFail();
    $placer = fn (string $code) => SituationCarriere::where('salarie_id', $salarie->id)
        ->update(['position_classification_id' => ($this->position)($code)->id, 'date_effet_echelon' => '2023-01-01', 'date_reference_avancement' => '2023-01-01']);

    $placer('E4-C');
    $fenetre = app(CalculateurEligibilite::class)->fenetreCategorie($salarie->fresh());
    expect($fenetre['categorie_suivante'])->toBe('E5');
    expect([$fenetre['ouverture'], $fenetre['limite']])->toBe(['2026-01-01', '2027-01-01']);

    $placer('E6-C');
    $fenetre = app(CalculateurEligibilite::class)->fenetreCategorie($salarie->fresh());
    expect($fenetre['hors_categorie'])->toBeTrue();
    expect($fenetre['categorie_suivante'])->toBeNull();

    $placer('E1-U');
    expect(app(CalculateurEligibilite::class)->fenetreCategorie($salarie->fresh())['categorie_suivante'])->toBe('E2');
});
