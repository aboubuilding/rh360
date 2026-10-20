<?php

/*
| Lot 5 — travail intérimaire par agence (Code du travail, art. 60 à 63, 356 — RG-TT-01 à RG-TT-10).
*/

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Classification\Models\CategorieClassification;
use App\Domain\Classification\Models\EchelonClassification;
use App\Domain\Classification\Models\PositionClassification;
use App\Domain\Classification\Models\ReferentielClassification;
use App\Domain\Conges\Models\Absence;
use App\Domain\Conges\Models\TypeConge;
use App\Domain\Contrats\Enums\TypeContrat;
use App\Domain\Interim\Enums\StatutAgrement;
use App\Domain\Interim\Enums\StatutMission;
use App\Domain\Interim\Enums\TypeDecisionInterim;
use App\Domain\Interim\Enums\TypeMotifMission;
use App\Domain\Interim\Models\AlerteInterim;
use App\Domain\Interim\Models\EntrepriseInterim;
use App\Domain\Interim\Models\MissionInterim;
use App\Domain\Interim\Models\ParametreInterim;
use App\Domain\Interim\Services\BalayeurInterims;
use App\Domain\Interim\Services\MissionInterimService;
use App\Domain\Organisation\Models\Poste;
use App\Domain\Paie\Models\BulletinPaie;
use App\Domain\Paie\Models\PeriodePaie;
use App\Domain\Paie\Models\SaisiePaie;
use App\Domain\Paie\Services\MoteurPaie;
use App\Domain\Personnel\Models\Affectation;
use App\Domain\Personnel\Models\Salarie;
use App\Domain\Pilotage\Services\CalculateurIndicateursPersonnel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed();
    $this->actingAs(Utilisateur::where('role', Utilisateur::ROLE_SUPER_ADMIN)->first());
    Storage::fake('local');

    $this->agence = EntrepriseInterim::create([
        'entreprise_id' => 1, 'raison_sociale' => 'Togo Intérim SARL', 'numero_agrement' => 'AG-2025-014',
        'date_agrement' => '2025-01-15', 'statut_agrement' => StatutAgrement::VALIDE->value,
    ]);
    $this->poste = Poste::where('intitule', 'Comptable')->firstOrFail();
    $this->service = app(MissionInterimService::class);
});

function donneesMission(array $surcharges = []): array
{
    return array_merge([
        'entreprise_interim_id' => test()->agence->id,
        'interimaire_nom' => 'KOFFI',
        'interimaire_prenoms' => 'Yao',
        'poste_id' => test()->poste->id,
        'qualification_convenue' => 'Aide-comptable',
        'travail_determine' => 'Saisie des pièces comptables du trimestre',
        'type_motif' => TypeMotifMission::AUTRE->value,
        'motif' => 'Surcroît temporaire de travail de clôture',
        'date_debut' => '2026-11-02',
        'date_fin' => '2026-12-31',
        'salaire_convenu' => 150000,
    ], $surcharges);
}

function erreursMission(callable $action): array
{
    try {
        $action();
    } catch (ValidationException $e) {
        return $e->errors();
    }

    throw new RuntimeException('Une ValidationException était attendue.');
}

function absenceGreve(Salarie $salarie, string $debut, ?string $fin): Absence
{
    return Absence::create([
        'entreprise_id' => 1,
        'salarie_id' => $salarie->id,
        'type_conge_id' => TypeConge::where('code', 'GREVE')->value('id'),
        'debut_le' => $debut,
        'fin_le' => $fin,
    ]);
}

// --- RG-TT-01 ---

it('RG-TT-01 : refuse une mission avec une agence sans agrément, suspendue ou dont l\'agrément expire', function (array $agrement) {
    $this->agence->update($agrement);

    $erreurs = erreursMission(fn () => $this->service->creer(donneesMission()));

    expect($erreurs['entreprise_interim_id'][0])->toContain('agrément valide du ministre chargé du travail')->toContain('art. 60 à 63');
    expect(MissionInterim::count())->toBe(0);
})->with([
    'sans numéro d\'agrément' => [['numero_agrement' => null, 'date_agrement' => null]],
    'agrément suspendu' => [['statut_agrement' => StatutAgrement::SUSPENDU->value]],
    'agrément retiré' => [['statut_agrement' => StatutAgrement::RETIRE->value]],
    'agrément obtenu après le début' => [['date_agrement' => '2026-11-15']],
    'agrément expirant avant la fin' => [['date_fin_validite_agrement' => '2026-12-01']],
]);

// --- RG-TT-02 ---

it('RG-TT-02 : enregistre la mission avec l\'intérimaire, l\'agence, le poste, la qualification, le travail et le motif', function () {
    $mission = $this->service->creer(donneesMission());

    expect($mission->reference)->toBe('MIS-'.now()->year.'-00001');
    expect($mission->statut)->toBe(StatutMission::PLANIFIEE);
    expect($mission->agence->is($this->agence))->toBeTrue();
    expect($mission->qualification_convenue)->toBe('Aide-comptable');
    expect($mission->travail_determine)->toContain('pièces comptables');
    expect($mission->decisions()->first()->type_decision)->toBe(TypeDecisionInterim::CREATION);
});

// --- RG-TT-03 ---

it('RG-TT-03 : n\'ajoute pas l\'intérimaire à l\'effectif', function () {
    $effectif = fn () => app(CalculateurIndicateursPersonnel::class)->calculer(1)->firstWhere('cle', 'effectif_actif')->valeur;
    $avant = [Salarie::count(), $effectif()];

    $mission = $this->service->creer(donneesMission(['date_debut' => today()->toDateString(), 'date_fin' => today()->addMonth()->toDateString()]));
    $this->service->validerChecklist($mission, array_fill_keys(['sst_accueil_le', 'sst_epi_remis_le', 'sst_consignes_le'], today()->toDateString()));
    $this->service->demarrer($mission->fresh());

    expect([Salarie::count(), $effectif()])->toBe($avant);
    expect(Schema::hasColumn('missions_interim', 'salarie_id'))->toBeFalse();
});

// --- RG-TT-04 / RG-TT-08 ---

it('RG-TT-04 / RG-TT-08 : ne calcule ni paie ni cotisations pour l\'intérimaire, et n\'enregistre que la facture', function () {
    $periode = PeriodePaie::where('statut', 'open')->firstOrFail();
    $mission = $this->service->creer(donneesMission(['date_debut' => today()->toDateString(), 'date_fin' => today()->addMonth()->toDateString()]));

    app(MoteurPaie::class)->calculer($periode);
    $facture = $this->service->enregistrerFacture($this->agence, [
        'mission_interim_id' => $mission->id, 'numero_facture' => 'F-2026-118', 'date_facture' => today()->toDateString(), 'montant' => 210000,
    ], UploadedFile::fake()->create('facture.pdf', 10, 'application/pdf'));

    expect(BulletinPaie::where('periode_id', $periode->id)->count())->toBe(Salarie::where('actif', true)->count());
    expect(SaisiePaie::where('periode_id', $periode->id)->where('observations', 'like', '%KOFFI%')->count())->toBe(0);
    expect($facture->montant)->toBe(210000);
    Storage::disk('local')->assertExists($facture->chemin_piece);
    expect($mission->factures()->count())->toBe(1);
});

// --- RG-TT-05 / RG-TT-10 ---

it('RG-TT-05 / RG-TT-10 : refuse le remplacement de gréviste et affiche la sanction encourue', function () {
    $erreurs = erreursMission(fn () => $this->service->creer(donneesMission(['type_motif' => TypeMotifMission::REMPLACEMENT_GREVISTE->value])));

    expect($erreurs['type_motif'][0])
        ->toContain('Mission refusée : remplacement de gréviste interdit (Code du travail, art. 62)')
        ->toContain('art. 356')
        ->toContain('500 000 à 2 000 000 FCFA')
        ->toContain('1 000 000 à 5 000 000 FCFA')
        ->toContain('1 à 6 mois');
});

it('RG-TT-05 : refuse une mission sur un poste dont le titulaire est en grève sur la période', function () {
    $titulaire = Salarie::findOrFail(Affectation::where('poste_id', $this->poste->id)->where('en_cours', true)->value('salarie_id'));
    absenceGreve($titulaire, '2026-12-10 00:00:00', '2026-12-12 00:00:00');

    $erreurs = erreursMission(fn () => $this->service->creer(donneesMission()));

    expect($erreurs['poste_id'][0])->toContain('gréviste')->toContain($titulaire->nom_complet)->toContain('art. 356');
});

it('RG-TT-05 : refuse le remplacement d\'un salarié désigné en grève, et accepte hors période de grève', function () {
    $remplace = Salarie::orderByDesc('id')->firstOrFail();
    absenceGreve($remplace, '2026-10-01 00:00:00', '2026-10-05 00:00:00');
    $donnees = donneesMission(['type_motif' => TypeMotifMission::REMPLACEMENT_ABSENCE->value, 'titulaire_remplace_id' => $remplace->id]);

    expect(erreursMission(fn () => $this->service->creer(array_merge($donnees, ['date_debut' => '2026-10-03']))))->toHaveKey('poste_id');
    expect($this->service->creer($donnees)->exists)->toBeTrue();
});

// --- RG-TT-06 / RG-TT-10 ---

it('RG-TT-06 / RG-TT-10 : refuse une mission sur un poste à travaux particulièrement dangereux', function () {
    $this->poste->update(['travaux_dangereux' => true]);

    $erreurs = erreursMission(fn () => $this->service->creer(donneesMission()));

    expect($erreurs['poste_id'][0])->toContain('travaux particulièrement dangereux')->toContain('art. 356');
});

// --- RG-TT-07 ---

it('RG-TT-07 : refuse de démarrer une mission dont la checklist SST n\'est pas validée', function () {
    $mission = $this->service->creer(donneesMission());

    $erreurs = erreursMission(fn () => $this->service->demarrer($mission));
    expect($erreurs['checklist'][0])->toContain('checklist SST')->toContain('santé, de la sécurité et de l\'hygiène');

    $this->service->validerChecklist($mission, ['sst_accueil_le' => '2026-10-30', 'sst_epi_remis_le' => '2026-10-30', 'sst_consignes_le' => '2026-10-31']);
    expect($this->service->demarrer($mission->fresh())->statut)->toBe(StatutMission::EN_COURS);
    expect($mission->decisions()->where('type_decision', TypeDecisionInterim::CHECKLIST_SST->value)->sole()->decide_par)->toBe(auth()->id());
});

it('RG-TT-07 : alerte avant le début d\'une mission sans checklist SST, puis clôt l\'alerte', function () {
    $mission = $this->service->creer(donneesMission());
    $alerte = fn () => AlerteInterim::where('objet_type', $mission->getMorphClass())->where('objet_id', $mission->id)->first();
    $balayer = function (string $date) {
        $this->travelTo($date.' 06:15:00');
        app(BalayeurInterims::class)->balayer(1);
    };

    $balayer('2026-10-25');
    expect($alerte())->toBeNull();

    $balayer('2026-10-26');
    expect($alerte()->niveau)->toBe('warning');

    $balayer('2026-11-02');
    expect($alerte()->niveau)->toBe('danger');

    $this->service->validerChecklist($mission, array_fill_keys(['sst_accueil_le', 'sst_epi_remis_le', 'sst_consignes_le'], '2026-11-02'));
    $balayer('2026-11-03');
    expect($alerte()->en_cours)->toBeFalse();
});

// --- RG-TT-09 ---

it('RG-TT-09 : alerte sans bloquer si le salaire convenu est inférieur au minimum CCIT de la catégorie', function () {
    $ccit = ReferentielClassification::create(['entreprise_id' => 1, 'code' => 'CCIT', 'nom' => 'CCIT', 'niveau_source' => 'ccit', 'actif' => true]);
    $m1 = CategorieClassification::create(['referentiel_id' => $ccit->id, 'code' => 'M1', 'libelle' => 'M1', 'ordre' => 10]);
    $a = EchelonClassification::create(['referentiel_id' => $ccit->id, 'code' => 'A', 'libelle' => 'A', 'ordre' => 1]);
    PositionClassification::create(['referentiel_id' => $ccit->id, 'code' => 'M1-A', 'categorie_id' => $m1->id, 'echelon_id' => $a->id, 'salaire_minimum' => 103500, 'actif' => true]);
    $this->poste->update(['categorie_classification_id' => $m1->id]);

    $basse = $this->service->creer(donneesMission(['salaire_convenu' => 90000]));
    $conforme = $this->service->creer(donneesMission(['salaire_convenu' => 103500]));

    expect($basse->alerte_salaire)->toContain('inférieur au minimum')->toContain('103 500 FCFA')->toContain('art. 155 ; CCIT, art. 28');
    expect($basse->salaire_minimum_reference)->toBe(103500);
    expect($conforme->alerte_salaire)->toBeNull();
});

// --- Extension décret n°2022-027/PR ---

it('applique la durée maximale et le nombre de renouvellements seulement s\'ils sont paramétrés', function () {
    $mission = $this->service->creer(donneesMission(['date_fin' => '2027-06-30']));
    expect($mission->exists)->toBeTrue();

    ParametreInterim::create(['entreprise_id' => 1, 'valeurs' => ['missions_duree_max_mois' => 3, 'missions_renouvellements_max' => 1]]);
    app()->forgetScopedInstances(); // les paramètres sont lus une fois par requête
    $service = app(MissionInterimService::class);

    expect(erreursMission(fn () => $service->creer(donneesMission(['date_fin' => '2027-02-02']))))->toHaveKey('date_fin');

    $courte = $service->creer(donneesMission(['date_fin' => '2026-12-01']));
    $courte = $service->renouveler($courte, '2026-12-31');
    expect($courte->nombre_renouvellements)->toBe(1);
    expect(erreursMission(fn () => $service->renouveler($courte, '2027-01-15')))->toHaveKey('date_fin');
});

// --- Contrats (RG-TT-03 / RG-TT-04) ---

it('refuse un nouveau contrat de type « Intérim » : l\'agence est le seul employeur', function () {
    $validateur = Validator::make(['type_contrat' => TypeContrat::INTERIM->value], ['type_contrat' => [TypeContrat::regleHorsInterim()]]);

    expect($validateur->fails())->toBeTrue();
    expect($validateur->errors()->first('type_contrat'))->toContain('mission d\'intérim')->toContain('art. 60 à 63');
    expect(Validator::make(['type_contrat' => 'CDD'], ['type_contrat' => [TypeContrat::regleHorsInterim()]])->fails())->toBeFalse();
});

it('crée le type d\'absence « Grève » pour l\'entreprise', function () {
    expect(TypeConge::where('code', 'GREVE')->value('nom'))->toBe('Grève');
});

// --- Écrans ---

it('enregistre une agence et une mission depuis les écrans et affiche les refus en français', function () {
    $this->postJson(route('carriere.interims.agences.store'), [
        'raison_sociale' => 'Lomé Travail Temporaire', 'numero_agrement' => 'AG-2026-002',
        'date_agrement' => '2026-01-10', 'statut_agrement' => 'valide',
    ])->assertOk();

    $this->postJson(route('carriere.interims.missions.store'), donneesMission(['type_motif' => TypeMotifMission::REMPLACEMENT_GREVISTE->value]))
        ->assertStatus(422)->assertJsonValidationErrors(['type_motif']);

    $this->postJson(route('carriere.interims.missions.store'), donneesMission())->assertOk()->assertJsonPath('message', 'Mission enregistrée.');
    $mission = MissionInterim::sole();

    $this->get(route('carriere.interims.agences.index'))->assertOk()->assertSee('Lomé Travail Temporaire');
    $this->get(route('carriere.interims.missions.index'))->assertOk()->assertSee('KOFFI');
    $this->get(route('carriere.interims.missions.create'))->assertOk()->assertSee('art. 356');
    $this->get(route('carriere.interims.missions.show', $mission))->assertOk()->assertSee('Checklist SST');

    $this->postJson(route('carriere.interims.missions.demarrer', $mission))->assertStatus(422);
    $this->post(route('carriere.interims.missions.checklist', $mission), array_fill_keys(['sst_accueil_le', 'sst_epi_remis_le', 'sst_consignes_le'], '2026-10-30'), ['Accept' => 'application/json'])->assertOk();
    $this->postJson(route('carriere.interims.missions.demarrer', $mission))->assertOk();
    $this->postJson(route('carriere.interims.missions.terminer', $mission), ['date_fin_reelle' => '2026-12-20'])->assertOk();

    expect($mission->fresh()->statut)->toBe(StatutMission::TERMINEE);
});
