<?php

/*
| Paramétrage des avancements et de l'ancienneté (points d'interprétation CCIT 2026 art. 33, 40 ;
| Code art. 46, 65) et leur application aux calculs : ancienneté utile, prime, échelon, catégorie.
| Barème de test : Annexe II CCIT 2026, E4 = 71 500 / 75 500 / 79 500 FCFA.
*/

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Carriere\Models\ParametreAvancement;
use App\Domain\Carriere\Models\SituationCarriere;
use App\Domain\Carriere\Services\CalculateurAncienneteUtile;
use App\Domain\Carriere\Services\CalculateurEligibilite;
use App\Domain\Carriere\Services\CatalogueParametresAvancement;
use App\Domain\Classification\Models\CategorieClassification;
use App\Domain\Classification\Models\EchelonClassification;
use App\Domain\Classification\Models\PositionClassification;
use App\Domain\Classification\Models\ReferentielClassification;
use App\Domain\Conges\Enums\ImpactAnciennete;
use App\Domain\Conges\Models\Absence;
use App\Domain\Conges\Models\TypeConge;
use App\Domain\Interim\Models\InterimInterne;
use App\Domain\Paie\Models\RegleAnciennete;
use App\Domain\Paie\Services\CalculateurAnciennete;
use App\Domain\Personnel\Models\Salarie;
use Carbon\Carbon;

beforeEach(function () {
    $this->seed();
    $this->actingAs(Utilisateur::where('role', Utilisateur::ROLE_SUPER_ADMIN)->first());

    // Grille CCIT (extrait) : E4-A → E4-B → E4-C, puis E5.
    $ccit = ReferentielClassification::create(['entreprise_id' => 1, 'code' => 'CCIT-2026', 'nom' => 'Grille 2026', 'niveau_source' => 'ccit', 'actif' => true]);
    $echelons = collect(['A' => 1, 'B' => 2, 'C' => 3])->map(fn ($ordre, $code) => EchelonClassification::create(['referentiel_id' => $ccit->id, 'code' => $code, 'libelle' => $code, 'ordre' => $ordre]));
    $this->e4 = CategorieClassification::create(['referentiel_id' => $ccit->id, 'code' => 'E4', 'libelle' => 'E4', 'ordre' => 4]);
    $this->e5 = CategorieClassification::create(['referentiel_id' => $ccit->id, 'code' => 'E5', 'libelle' => 'E5', 'ordre' => 5]);
    $this->e4c = PositionClassification::create(['referentiel_id' => $ccit->id, 'code' => 'E4-C', 'categorie_id' => $this->e4->id, 'echelon_id' => $echelons['C']->id, 'salaire_minimum' => 79500, 'actif' => true]);
    $this->e4b = PositionClassification::create(['referentiel_id' => $ccit->id, 'code' => 'E4-B', 'categorie_id' => $this->e4->id, 'echelon_id' => $echelons['B']->id, 'salaire_minimum' => 75500, 'position_suivante_id' => $this->e4c->id, 'actif' => true]);
    $this->e4a = PositionClassification::create(['referentiel_id' => $ccit->id, 'code' => 'E4-A', 'categorie_id' => $this->e4->id, 'echelon_id' => $echelons['A']->id, 'salaire_minimum' => 71500, 'position_suivante_id' => $this->e4b->id, 'actif' => true]);

    $this->salarie = Salarie::orderBy('id')->firstOrFail();
    $this->placer = fn (PositionClassification $position, string $depuis) => SituationCarriere::where('salarie_id', $this->salarie->id)
        ->update(['position_classification_id' => $position->id, 'date_effet_echelon' => $depuis, 'date_reference_avancement' => $depuis]);
    $this->absence = fn (string $code, string $debut, ?string $fin, array $extra = []) => Absence::create(array_merge([
        'entreprise_id' => 1, 'salarie_id' => $this->salarie->id,
        'type_conge_id' => TypeConge::where('code', $code)->value('id'), 'debut_le' => $debut, 'fin_le' => $fin,
    ], $extra));
});

function parametrerAvancement(array $valeurs): void
{
    ParametreAvancement::updateOrCreate(['entreprise_id' => 1], ['valeurs' => $valeurs]);
    app()->forgetScopedInstances();
}

// --- Taux de la prime (point 2) ---

it('applique la lecture « cumul » : 2 % à 2 et 3 ans, 3 % à 4 ans, 35 % à 36 ans', function (int $annees, float $taux) {
    $regle = RegleAnciennete::make(['annees_min' => 2, 'taux_initial' => 2, 'increment_annuel' => 1, 'taux_max' => 35, 'mode_taux' => 'ccit_cumul', 'annee_debut_increment' => 4]);

    expect($regle->calculerTaux($annees))->toBe($taux);
})->with([[1, 0.0], [2, 2.0], [3, 2.0], [4, 3.0], [10, 9.0], [35, 34.0], [36, 35.0], [40, 35.0]]);

it('applique la lecture « multiplicatif » : 4 % à 4 ans, 35 % à 35 ans', function (int $annees, float $taux) {
    $regle = RegleAnciennete::make(['annees_min' => 2, 'taux_initial' => 2, 'increment_annuel' => 1, 'taux_max' => 35, 'mode_taux' => 'ccit_multiplicatif', 'annee_debut_increment' => 4]);

    expect($regle->calculerTaux($annees))->toBe($taux);
})->with([[3, 2.0], [4, 4.0], [10, 10.0], [35, 35.0], [36, 35.0]]);

// --- Base de la prime (point 1) ---

it('calcule la prime sur le minimum de la catégorie, de l\'échelon ou sur le salaire réel selon la règle', function (string $mode, float $attendu) {
    ($this->placer)($this->e4b, '2024-01-01');
    $this->salarie->update(['date_embauche' => now()->subYears(10)]);
    RegleAnciennete::query()->update(['mode_base' => $mode]);

    // 10 ans : 9 %. E4-A = 71 500 ; E4-B = 75 500 ; salaire réel = 90 000.
    expect(app(CalculateurAnciennete::class)->calculerPrime($this->salarie, 90000, now()))->toBe($attendu);
})->with([
    'minimum de la catégorie' => ['echelon_a', 6435.0],
    'minimum de l\'échelon' => ['echelon_salarie', 6795.0],
    'salaire réel' => ['salary_base', 8100.0],
]);

it('arrondit la prime selon le paramètre', function () {
    ($this->placer)($this->e4b, '2024-01-01');
    $this->salarie->update(['date_embauche' => now()->subYears(10)]);
    RegleAnciennete::query()->update(['mode_base' => 'echelon_salarie']);
    parametrerAvancement(['arrondi_montants' => '10']);

    // 75 500 × 9 % = 6 795 → 6 800 aux 10 FCFA
    expect(app(CalculateurAnciennete::class)->calculerPrime($this->salarie, 90000, now()))->toBe(6800.0);
});

it('proratise le mois où un seuil est franchi si le paramètre le prévoit', function () {
    ($this->placer)($this->e4a, '2024-01-01');
    $this->salarie->update(['date_embauche' => '2016-11-11']);
    $finMois = Carbon::parse('2026-11-30');
    $calcul = fn () => app(CalculateurAnciennete::class)->calculerPrime($this->salarie, 0, $finMois);

    // 10 ans le 11/11/2026 : 8 % jusqu'au 10, 9 % ensuite. Mois entier par défaut : 71 500 × 9 % = 6 435.
    expect($calcul())->toBe(6435.0);

    parametrerAvancement(['prime_mois_seuil' => 'prorata_jours']);
    // (8 % × 10 j + 9 % × 20 j) / 30 j × 71 500 = 6 196,67 → 6 197
    expect($calcul())->toBe(6197.0);
});

it('retient la catégorie occupée pendant un intérim si le paramètre le prévoit', function () {
    ($this->placer)($this->e4a, '2024-01-01');
    $this->salarie->update(['date_embauche' => now()->subYears(10)]);
    $m1 = CategorieClassification::create(['referentiel_id' => $this->e4->referentiel_id, 'code' => 'M1', 'libelle' => 'M1', 'ordre' => 10]);
    PositionClassification::create(['referentiel_id' => $this->e4->referentiel_id, 'code' => 'M1-A', 'categorie_id' => $m1->id,
        'echelon_id' => $this->e4a->echelon_id, 'salaire_minimum' => 103500, 'actif' => true]);
    InterimInterne::factory()->create([
        'salarie_id' => $this->salarie->id, 'categorie_occupee_id' => $m1->id,
        'date_debut' => now()->subMonths(4), 'date_fin_prevue' => now()->addMonth(), 'date_limite' => now()->addMonth(),
    ]);

    expect(app(CalculateurAnciennete::class)->calculerPrime($this->salarie, 0, now()))->toBe(6435.0);

    parametrerAvancement(['prime_base_pendant_interim' => 'categorie_occupee']);
    // 103 500 × 9 % = 9 315
    expect(app(CalculateurAnciennete::class)->calculerPrime($this->salarie, 0, now()))->toBe(9315.0);
});

// --- Ancienneté utile ---

it('compte l\'essai et déduit les absences dont le type suspend l\'ancienneté', function () {
    $this->salarie->update(['date_embauche' => '2020-03-01']);
    TypeConge::where('code', 'SANS-SOLDE')->update(['impact_anciennete' => ImpactAnciennete::SUSPENDUE->value]);
    ($this->absence)('SANS-SOLDE', '2022-01-01 00:00:00', '2022-12-31 00:00:00');
    $calcul = app(CalculateurAncienneteUtile::class);

    expect($calcul->joursExclus($this->salarie, Carbon::parse('2020-03-01'), Carbon::parse('2026-03-01')))->toBe(365);
    expect($calcul->dateCorrigee($this->salarie, Carbon::parse('2026-03-01'))->toDateString())->toBe('2021-03-01');
    expect($calcul->annees($this->salarie, Carbon::parse('2026-03-01')))->toBe(5);
});

it('compte la maladie jusqu\'au remplacement du salarié', function () {
    $this->salarie->update(['date_embauche' => '2021-01-01']);
    $maladie = ($this->absence)('MAL', '2024-01-01 00:00:00', '2024-08-31 00:00:00');
    $calcul = fn () => app(CalculateurAncienneteUtile::class)->joursExclus($this->salarie, Carbon::parse('2021-01-01'), Carbon::parse('2026-01-01'));

    // Huit mois sans remplacement : rien n'est déduit.
    expect($calcul())->toBe(0);

    // Remplacé le 15/07/2024 : déduction du 15/07 au 31/08, soit 48 jours.
    $maladie->update(['date_remplacement' => '2024-07-15']);
    expect($calcul())->toBe(48);
});

it('compte les absences personnelles dans la limite paramétrée par année', function () {
    $this->salarie->update(['date_embauche' => '2020-01-01']);
    parametrerAvancement(['codes_absences_personnelles' => ['ABS-NJ'], 'absences_personnelles_limite_jours' => 10]);
    ($this->absence)('ABS-NJ', '2023-03-01 00:00:00', '2023-03-12 00:00:00');

    // 12 jours d'absence personnelle : 10 comptés, 2 déduits.
    expect(app(CalculateurAncienneteUtile::class)->joursExclus($this->salarie, Carbon::parse('2020-01-01'), Carbon::parse('2026-01-01')))->toBe(2);
});

// --- Avancement d'échelon et de catégorie ---

it('calcule l\'échéance d\'échelon à 2 ans par défaut, décalée des jours déduits', function () {
    ($this->placer)($this->e4a, '2024-05-01');
    $calcul = fn () => app(CalculateurEligibilite::class)->calculer($this->salarie->fresh());

    expect($calcul()['date_eligibilite'])->toBe('2026-05-01');
    expect($calcul()['delai_mois'])->toBe(24);
    expect($calcul()['mode'])->toBe('droit');

    TypeConge::where('code', 'SANS-SOLDE')->update(['impact_anciennete' => ImpactAnciennete::SUSPENDUE->value]);
    ($this->absence)('SANS-SOLDE', '2025-01-01 00:00:00', '2025-01-30 00:00:00');
    expect($calcul()['date_eligibilite'])->toBe('2026-05-31');

    parametrerAvancement(['periodes_avancement' => 'aucune_exclusion', 'delai_echelon_mois' => 18]);
    expect($calcul()['date_eligibilite'])->toBe('2025-11-01');
});

it('calcule la fenêtre de catégorie au dernier échelon : au moins 1 an et au plus 2 ans après 2 ans', function () {
    ($this->placer)($this->e4c, '2023-01-01');

    $fenetre = app(CalculateurEligibilite::class)->fenetreCategorie($this->salarie->fresh());

    expect($fenetre['ouverture'])->toBe('2026-01-01');
    expect($fenetre['limite'])->toBe('2027-01-01');
    expect($fenetre['categorie_suivante'])->toBe('E5');
    expect($fenetre['hors_categorie'])->toBeFalse();
    expect(app(CalculateurEligibilite::class)->calculer($this->salarie->fresh()))->toBeNull();
});

it('signale l\'épuisement de la classe (hors catégorie) au dernier échelon de la dernière catégorie', function () {
    $this->e5->delete();
    ($this->placer)($this->e4c, '2023-01-01');

    expect(app(CalculateurEligibilite::class)->fenetreCategorie($this->salarie->fresh())['hors_categorie'])->toBeTrue();
});

// --- Écrans ---

it('affiche et enregistre les paramètres des avancements sans référence aux textes', function () {
    $html = $this->get(route('carriere.avancements.parametres'))
        ->assertOk()
        ->assertSee('Ancienneté maximale dans un échelon (mois)')
        ->assertSee('Par défaut : 24')
        ->getContent();
    expect(preg_match('/\bart\.\s*\d|CCIT|Code du travail/u', strip_tags($html)))->toBe(0);

    $valeurs = collect(CatalogueParametresAvancement::definitions())
        ->mapWithKeys(fn ($d, $cle) => [$cle => is_bool(config("avancement.{$cle}")) ? (config("avancement.{$cle}") ? '1' : '0') : config("avancement.{$cle}")])
        ->all();
    $this->put(route('carriere.avancements.parametres.update'), array_merge($valeurs, ['delai_echelon_mois' => 18]))
        ->assertRedirect(route('carriere.avancements.parametres'));
    expect(ParametreAvancement::sole()->valeurs)->toBe(['delai_echelon_mois' => 18]);

    $this->post(route('carriere.avancements.parametres.reinitialiser'))->assertRedirect();
    expect(ParametreAvancement::sole()->valeurs)->toBe([]);
});

it('affiche les fenêtres de catégorie sur la page des avancements', function () {
    ($this->placer)($this->e4c, '2023-01-01');

    $this->get(route('carriere.avancements.index'))
        ->assertOk()
        ->assertSee('Fenêtres d\'avancement de catégorie', false)
        ->assertSee('Catégorie E5');
});

it('enregistre une règle d\'ancienneté avec son mode de calcul et sa base', function () {
    // Une seule règle par entreprise (index unique existant) : la règle en place est modifiée.
    $existante = RegleAnciennete::firstOrFail();
    $this->put(route('paie.parametres.anciennete.update', $existante), [
        'annees_min' => 2, 'taux_initial' => 2, 'increment_annuel' => 1, 'taux_max' => 35,
        'mode_taux' => 'ccit_multiplicatif', 'annee_debut_increment' => 4, 'mode_base' => 'echelon_salarie',
        'debut_effet' => '2026-01-01', 'actif' => '1',
    ])->assertRedirect();

    $regle = $existante->fresh();
    expect($regle->mode_taux)->toBe('ccit_multiplicatif');
    expect($regle->mode_base)->toBe('echelon_salarie');
});

// --- Règle d'ancienneté versionnée ---

it('crée une nouvelle version datée de la règle d\'ancienneté et applique la version en vigueur', function () {
    $this->salarie->update(['date_embauche' => '2016-01-01']);
    RegleAnciennete::query()->update(['mode_base' => 'salary_base']);

    $this->post(route('paie.parametres.anciennete.store'), [
        'annees_min' => 2, 'taux_initial' => 2, 'increment_annuel' => 1, 'taux_max' => 5,
        'mode_taux' => 'ccit_cumul', 'annee_debut_increment' => 4, 'mode_base' => 'salary_base',
        'debut_effet' => '2027-01-01', 'actif' => '1',
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect(RegleAnciennete::count())->toBe(2);
    $calcul = app(CalculateurAnciennete::class);
    // Fin 2026 : version d'origine, 10 ans → 9 %. Début 2027 : nouvelle version plafonnée à 5 %.
    expect($calcul->calculerTaux($this->salarie, Carbon::parse('2026-12-31')))->toBe(9.0);
    expect($calcul->calculerTaux($this->salarie, Carbon::parse('2027-01-31')))->toBe(5.0);

    // Deux versions ne peuvent pas commencer à la même date.
    $this->post(route('paie.parametres.anciennete.store'), [
        'annees_min' => 2, 'taux_initial' => 2, 'increment_annuel' => 1, 'taux_max' => 35,
        'mode_taux' => 'ccit_cumul', 'annee_debut_increment' => 4, 'mode_base' => 'salary_base',
        'debut_effet' => '2027-01-01', 'actif' => '1',
    ])->assertSessionHasErrors('debut_effet');
});
