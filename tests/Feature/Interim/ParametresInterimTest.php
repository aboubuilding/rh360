<?php

/*
| Paramétrage des intérims : valeurs des textes par défaut, surcharge par entreprise,
| reclassement programmé, barème choisi, catégorie et dangerosité des postes.
*/

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Carriere\Enums\StatutMouvement;
use App\Domain\Carriere\Models\SituationCarriere;
use App\Domain\Classification\Models\CategorieClassification;
use App\Domain\Classification\Models\EchelonClassification;
use App\Domain\Classification\Models\PositionClassification;
use App\Domain\Classification\Models\ReferentielClassification;
use App\Domain\Conges\Models\Absence;
use App\Domain\Conges\Models\TypeConge;
use App\Domain\Interim\Enums\DecisionFinInterim;
use App\Domain\Interim\Enums\MotifInterim;
use App\Domain\Interim\Models\ParametreInterim;
use App\Domain\Interim\Services\CatalogueParametresInterim;
use App\Domain\Interim\Services\InterimInterneService;
use App\Domain\Interim\Services\ParametresInterim;
use App\Domain\Interim\Services\RepertoireBareme;
use App\Domain\Organisation\Models\Poste;
use App\Domain\Paie\Models\ElementPaieSalarie;
use App\Domain\Personnel\Models\Salarie;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed();
    $this->actingAs(Utilisateur::where('role', Utilisateur::ROLE_SUPER_ADMIN)->first());
    Storage::fake('local');

    $this->categorieB = CategorieClassification::where('code', 'B')->firstOrFail();
    $this->salarie = Salarie::orderBy('id')->firstOrFail();
    $this->titulaire = Salarie::where('id', '!=', $this->salarie->id)->orderBy('id')->firstOrFail();
    SituationCarriere::where('salarie_id', $this->salarie->id)
        ->update(['position_classification_id' => PositionClassification::where('code', 'A1-A')->value('id')]);
    $this->poste = Poste::where('intitule', 'Gestionnaire RH')->firstOrFail();
    $this->poste->update(['categorie_classification_id' => $this->categorieB->id]);

    $this->parametres = app(ParametresInterim::class);
    $this->service = app(InterimInterneService::class);
});

function interimParametre(array $surcharges = []): array
{
    return array_merge([
        'salarie_id' => test()->salarie->id,
        'poste_occupe_id' => test()->poste->id,
        'titulaire_id' => test()->titulaire->id,
        'motif' => MotifInterim::POSTE_VACANT->value,
        'date_debut' => '2026-09-19',
        'date_fin_prevue' => '2026-11-19',
    ], $surcharges);
}

function enregistrerParametres(array $valeurs): void
{
    test()->put(route('carriere.interims.parametres.update'), array_merge(
        collect(CatalogueParametresInterim::definitions())
            ->mapWithKeys(fn ($d, $cle) => [$cle => match (true) {
                is_bool(test()->parametres->get($cle)) => test()->parametres->get($cle) ? '1' : '0',
                default => test()->parametres->get($cle),
            }])->all(),
        $valeurs,
    ))->assertRedirect(route('carriere.interims.parametres'));
}

it('affiche l\'écran des paramètres avec les valeurs des textes et leurs références', function () {
    $this->get(route('carriere.interims.parametres'))
        ->assertOk()
        ->assertSee('Durée maximale sans motif éligible (mois)')
        ->assertSee('CCIT, art. 13')
        ->assertSee('Code du travail, art. 65 c ; CCIT, art. 14')
        ->assertSee('Non tranché par les textes');
});

it('applique la durée maximale saisie par l\'entreprise au lieu de la valeur des textes', function () {
    enregistrerParametres(['duree_max_interim_mois' => 3]);

    $interim = $this->service->creer(interimParametre(['date_fin_prevue' => '2026-12-19']));

    expect($interim->date_limite->toDateString())->toBe('2026-12-19');
    expect(app(ParametresInterim::class)->estSurcharge('duree_max_interim_mois'))->toBeTrue();
});

it('ne conserve pas comme surcharge une valeur égale à celle des textes', function () {
    enregistrerParametres(['duree_max_interim_mois' => 2, 'delai_maladie_mois' => 4]);

    expect(ParametreInterim::sole()->valeurs)->toBe(['delai_maladie_mois' => 4]);
});

it('rétablit les valeurs des textes', function () {
    enregistrerParametres(['duree_max_interim_mois' => 3]);

    $this->post(route('carriere.interims.parametres.reinitialiser'))->assertRedirect();

    expect(ParametreInterim::sole()->valeurs)->toBe([]);
    expect(app(ParametresInterim::class)->get('duree_max_interim_mois'))->toBe(2);
});

it('refuse une valeur de paramètre invalide', function () {
    $this->put(route('carriere.interims.parametres.update'), ['duree_max_interim_mois' => 0])
        ->assertSessionHasErrors('duree_max_interim_mois');
});

it('cloisonne les paramètres par entreprise', function () {
    ParametreInterim::create(['entreprise_id' => 1, 'valeurs' => ['duree_max_interim_mois' => 4]]);

    expect($this->parametres->pourEntreprise(1)->get('duree_max_interim_mois'))->toBe(4);
    expect(app(ParametresInterim::class)->pourEntreprise(999)->get('duree_max_interim_mois'))->toBe(2);
});

// RG-INT-05 — point de départ des délais légaux
it('fait courir par défaut le délai maladie depuis le début de l\'absence du titulaire', function () {
    $absence = Absence::create([
        'entreprise_id' => 1,
        'salarie_id' => $this->titulaire->id,
        'type_conge_id' => TypeConge::where('code', 'MAL')->value('id'),
        'debut_le' => '2026-08-01 00:00:00',
    ]);
    $donnees = interimParametre([
        'motif' => MotifInterim::MALADIE_ACCIDENT->value,
        'absence_titulaire_id' => $absence->id,
        'date_fin_prevue' => '2027-03-19',
    ]);

    expect(fn () => $this->service->creer($donnees))->toThrow(ValidationException::class);

    enregistrerParametres(['origine_delai_legal' => 'debut_interim']);
    $interim = app(InterimInterneService::class)->creer($donnees);

    expect($interim->date_limite->toDateString())->toBe('2027-03-19');
});

it('applique le délai maladie saisi par l\'entreprise', function () {
    enregistrerParametres(['delai_maladie_mois' => 4]);

    expect(fn () => app(InterimInterneService::class)->creer(interimParametre([
        'motif' => MotifInterim::MALADIE_ACCIDENT->value,
        'date_fin_prevue' => '2027-01-20',
    ])))->toThrow(ValidationException::class);
});

// RG-INT-08 — reclassement à une date d'effet future
it('programme un reclassement daté dans le futur et l\'applique à sa date', function () {
    $interim = $this->service->creer(interimParametre());
    $position = PositionClassification::where('code', 'B1-A')->firstOrFail();
    $positionAvant = SituationCarriere::where('salarie_id', $this->salarie->id)->value('position_classification_id');
    $salaireDe = fn () => (int) ElementPaieSalarie::where('salarie_id', $this->salarie->id)
        ->whereHas('rubrique', fn ($q) => $q->where('code', 'SAL-BASE'))->value('montant');
    $salaireAvant = $salaireDe();

    $dateEffet = today()->addMonth();
    $clos = $this->service->deciderFin($interim, DecisionFinInterim::RECLASSEMENT, $dateEffet->toDateString(),
        UploadedFile::fake()->create('decision.pdf', 20, 'application/pdf'), $position->id, 150000);

    expect($clos->mouvementReclassement->statut)->toBe(StatutMouvement::PROGRAMME);
    expect($clos->reclassement_applique_le)->toBeNull();
    expect(SituationCarriere::where('salarie_id', $this->salarie->id)->value('position_classification_id'))->toBe($positionAvant);
    expect($salaireDe())->toBe($salaireAvant);

    $this->artisan('interims:appliquer-reclassements')->assertSuccessful();
    expect($clos->fresh()->reclassement_applique_le)->toBeNull();

    $this->travelTo($dateEffet->copy()->addHour());
    $this->artisan('interims:appliquer-reclassements')->assertSuccessful();

    expect($clos->fresh()->reclassement_applique_le)->not->toBeNull();
    expect(SituationCarriere::where('salarie_id', $this->salarie->id)->value('position_classification_id'))->toBe($position->id);
    expect($salaireDe())->toBe(150000);
});

// RG-INT-06 — barème choisi dans les paramètres
it('lit le minimum dans le référentiel de barème choisi et retient la convention plus favorable', function () {
    $creerGrille = function (string $code, string $niveau, int $minimum) {
        $referentiel = ReferentielClassification::create([
            'entreprise_id' => 1, 'code' => $code, 'nom' => $code, 'niveau_source' => $niveau, 'actif' => true,
        ]);
        $categorie = CategorieClassification::create(['referentiel_id' => $referentiel->id, 'code' => 'M1', 'libelle' => 'M1', 'ordre' => 10]);
        $echelon = EchelonClassification::create(['referentiel_id' => $referentiel->id, 'code' => 'A', 'libelle' => 'A', 'ordre' => 1]);
        PositionClassification::create([
            'referentiel_id' => $referentiel->id, 'code' => "{$code}-M1-A", 'categorie_id' => $categorie->id,
            'echelon_id' => $echelon->id, 'salaire_minimum' => $minimum, 'actif' => true,
        ]);

        return [$referentiel, $categorie];
    };

    [$ccit, $m1] = $creerGrille('CCIT-2026', 'conventionnel', 103500);
    [$btp] = $creerGrille('BTP-2026', 'conventionnel', 110000);
    $bareme = app(RepertoireBareme::class);

    expect($bareme->salaireMinimum($m1, today()))->toBe(103500);

    enregistrerParametres(['referentiel_bareme_id' => $ccit->id, 'referentiels_conventions_ids' => [$btp->id]]);

    expect(app(RepertoireBareme::class)->salaireMinimum($m1, today()))->toBe(110000);
});

// RG-INT-01 / RG-TT-06 — catégorie et dangerosité saisies dans le formulaire Poste
it('enregistre la catégorie de classification et la dangerosité d\'un poste', function () {
    $this->put(route('organisation.postes.update', $this->poste), [
        'structure_id' => $this->poste->structure_id,
        'code' => $this->poste->code,
        'intitule' => $this->poste->intitule,
        'categorie_classification_id' => $this->categorieB->id,
        'travaux_dangereux' => '1',
        'actif' => '1',
    ])->assertRedirect();

    expect($this->poste->fresh()->categorie_classification_id)->toBe($this->categorieB->id);
    expect($this->poste->fresh()->travaux_dangereux)->toBeTrue();
    $this->get(route('organisation.postes.index'))->assertOk()->assertSee('Catégorie de classification');
});
