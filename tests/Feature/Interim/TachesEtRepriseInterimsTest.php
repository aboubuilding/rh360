<?php

/*
| Lot 4 — tâches planifiées (interims:balayer), reprise des intérims antérieurs
| (interims:reprise) et rapport des écarts de paie sur les périodes validées.
*/

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Carriere\Enums\StatutMouvement;
use App\Domain\Carriere\Enums\TypeMouvement;
use App\Domain\Carriere\Models\MouvementCarriere;
use App\Domain\Carriere\Models\SituationCarriere;
use App\Domain\Classification\Models\CategorieClassification;
use App\Domain\Classification\Models\EchelonClassification;
use App\Domain\Classification\Models\PositionClassification;
use App\Domain\Classification\Models\ReferentielClassification;
use App\Domain\Conges\Models\Absence;
use App\Domain\Conges\Models\TypeConge;
use App\Domain\Contrats\Models\Contrat;
use App\Domain\Interim\Enums\CauseFinInterim;
use App\Domain\Interim\Enums\MotifInterim;
use App\Domain\Interim\Enums\StatutInterim;
use App\Domain\Interim\Enums\TypeDecisionInterim;
use App\Domain\Interim\Models\AlerteInterim;
use App\Domain\Interim\Models\InterimInterne;
use App\Domain\Interim\Services\BalayeurInterims;
use App\Domain\Interim\Services\ControleEcartsPaieInterims;
use App\Domain\Interim\Services\InterimInterneService;
use App\Domain\Interim\Services\RapportReprise;
use App\Domain\Interim\Services\RepriseInterims;
use App\Domain\Organisation\Models\Poste;
use App\Domain\Paie\Models\BulletinPaie;
use App\Domain\Paie\Models\ElementPaieSalarie;
use App\Domain\Paie\Models\PeriodePaie;
use App\Domain\Paie\Models\RubriquePaie;
use App\Domain\Paie\Models\SaisiePaie;
use App\Domain\Personnel\Models\Salarie;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed();
    $this->actingAs(Utilisateur::where('role', Utilisateur::ROLE_SUPER_ADMIN)->first());
    Storage::fake('local');

    // Grille de démonstration : A (ordre 1) < B (ordre 2).
    $this->categorieA = CategorieClassification::where('code', 'A')->firstOrFail();
    $this->categorieB = CategorieClassification::where('code', 'B')->firstOrFail();
    $this->salarie = Salarie::orderBy('id')->firstOrFail();
    $this->titulaire = Salarie::where('id', '!=', $this->salarie->id)->orderBy('id')->firstOrFail();
    SituationCarriere::where('salarie_id', $this->salarie->id)
        ->update(['position_classification_id' => PositionClassification::where('code', 'A1-A')->value('id')]);
    $this->posteB = Poste::where('intitule', 'Gestionnaire RH')->firstOrFail();
    $this->posteB->update(['categorie_classification_id' => $this->categorieB->id]);
    $this->posteA = Poste::where('intitule', 'Secrétaire')->firstOrFail();
    $this->posteA->update(['categorie_classification_id' => $this->categorieA->id]);

    $this->service = app(InterimInterneService::class);
});

function nouvelInterimTache(MotifInterim $motif, string $debut, string $fin): InterimInterne
{
    return test()->service->creer([
        'salarie_id' => test()->salarie->id,
        'poste_occupe_id' => test()->posteB->id,
        'titulaire_id' => test()->titulaire->id,
        'motif' => $motif->value,
        'date_debut' => $debut,
        'date_fin_prevue' => $fin,
    ]);
}

function balayerAu(string $date): array
{
    test()->travelTo(Carbon::parse($date)->setTime(6, 15));

    return app(BalayeurInterims::class)->balayer(1);
}

function alertesDe(InterimInterne $interim, bool $enCours = true)
{
    return AlerteInterim::where('objet_type', $interim->getMorphClass())->where('objet_id', $interim->id)
        ->where('en_cours', $enCours)->pluck('cle')->all();
}

function ligneReprise(RapportReprise $rapport, MouvementCarriere $mouvement): array
{
    return collect($rapport->lignes)->first(fn ($l) => $l['source'] === 'mouvement_carriere' && $l['id'] === $mouvement->id);
}

function interimHerite(array $attributs = []): MouvementCarriere
{
    return MouvementCarriere::factory()->interim()->effectif()->create(array_merge([
        'entreprise_id' => 1,
        'salarie_id' => test()->salarie->id,
        'poste_cible_id' => test()->posteB->id,
        'date_effet' => '2026-09-19',
        'date_fin_prevue' => '2026-11-18',
        'motif' => 'Poste vacant après départ',
    ], $attributs));
}

// --- Tâches planifiées ---

it('alerte 15 jours avant la fin des 2 mois, une seule fois', function () {
    $interim = nouvelInterimTache(MotifInterim::POSTE_VACANT, '2026-09-19', '2026-11-18');

    balayerAu('2026-11-02');
    expect(alertesDe($interim))->toBe([]);

    balayerAu('2026-11-03');
    balayerAu('2026-11-04');
    expect(alertesDe($interim))->toBe(['echeance_duree_max']);
    expect(AlerteInterim::count())->toBe(1);
    expect(AlerteInterim::first()->message)->toContain('ne permet pas de prolongation');
});

it('alerte avant la fin du délai légal d\'un motif éligible', function () {
    $interim = nouvelInterimTache(MotifInterim::MALADIE_ACCIDENT, '2026-08-01', '2027-01-31');

    balayerAu('2027-01-20');

    expect(alertesDe($interim))->toContain('echeance_delai_legal');
});

it('signale le passage au 3e mois et crée la rubrique d\'indemnité', function () {
    $interim = nouvelInterimTache(MotifInterim::MALADIE_ACCIDENT, '2026-08-01', '2026-12-31');

    balayerAu('2026-09-30');
    expect(alertesDe($interim))->not->toContain('debut_indemnite');

    balayerAu('2026-10-01');
    expect(alertesDe($interim))->toContain('debut_indemnite');
    expect(RubriquePaie::where('code', 'IND-INTERIM')->exists())->toBeTrue();
});

it('passe à régulariser à la date limite, sans clôturer ni dupliquer la décision', function () {
    $interim = nouvelInterimTache(MotifInterim::POSTE_VACANT, '2026-09-19', '2026-11-18');

    balayerAu('2026-11-18');
    expect($interim->fresh()->statut)->toBe(StatutInterim::EN_COURS);

    $bilan = balayerAu('2026-11-19');
    balayerAu('2026-11-20');

    $interim->refresh();
    expect($bilan['a_regulariser'])->toBe(1);
    expect($interim->statut)->toBe(StatutInterim::A_REGULARISER);
    expect($interim->date_fin_reelle)->toBeNull();
    expect(alertesDe($interim))->toContain('a_regulariser');
    expect($interim->decisions()->where('type_decision', TypeDecisionInterim::REGULARISATION->value)->count())->toBe(1);
});

it('clôt les alertes d\'un intérim clôturé', function () {
    $interim = nouvelInterimTache(MotifInterim::POSTE_VACANT, '2026-09-19', '2026-11-18');
    balayerAu('2026-11-05');

    $this->service->cloturer($interim, CauseFinInterim::RETOUR_TITULAIRE, '2026-11-05');
    balayerAu('2026-11-06');

    expect(alertesDe($interim))->toBe([]);
    expect(alertesDe($interim, false))->toBe(['echeance_duree_max']);
});

it('RG-REM-06 : alerte à 6 mois d\'absence maladie, puis clôt l\'alerte au retour', function () {
    $absence = Absence::create([
        'entreprise_id' => 1,
        'salarie_id' => $this->titulaire->id,
        'type_conge_id' => TypeConge::where('code', 'MAL')->value('id'),
        'debut_le' => '2026-03-01 00:00:00',
    ]);
    $alerte = fn () => AlerteInterim::where('objet_type', $absence->getMorphClass())->where('objet_id', $absence->id)->first();

    balayerAu('2026-08-31');
    expect($alerte())->toBeNull();

    balayerAu('2026-09-01');
    expect($alerte()->en_cours)->toBeTrue();
    expect($alerte()->message)->toContain('médecin-inspecteur du travail')->toContain('art. 65 c');

    $absence->update(['fin_le' => '2026-09-10 00:00:00']);
    balayerAu('2026-09-11');
    expect($alerte()->en_cours)->toBeFalse();
});

it('exécute la tâche planifiée et affiche les alertes dans le registre', function () {
    nouvelInterimTache(MotifInterim::POSTE_VACANT, '2026-09-19', '2026-11-18');
    $this->travelTo('2026-11-10 06:15:00');

    $this->artisan('interims:balayer')->assertSuccessful();

    $this->get(route('carriere.interims.index'))->assertOk()->assertSee('Alertes en cours')->assertSee('Fin des 2 mois');
});

// --- Reprise ---

it('reprend un intérim antérieur conforme, de façon idempotente et sans rien supprimer', function () {
    $herite = interimHerite();
    $mouvements = MouvementCarriere::count();

    $rapport = app(RepriseInterims::class)->executer(false);
    $interim = InterimInterne::where('mouvement_carriere_id', $herite->id)->sole();

    expect($interim->motif)->toBe(MotifInterim::POSTE_VACANT);
    expect($interim->statut)->toBe(StatutInterim::EN_COURS);
    expect($interim->date_limite->toDateString())->toBe('2026-11-18');
    expect($interim->reference_lot_reprise)->toBe($rapport->lot);
    expect($interim->decisions()->where('type_decision', TypeDecisionInterim::REPRISE->value)->count())->toBe(1);

    $second = app(RepriseInterims::class)->executer(false);
    expect(InterimInterne::count())->toBe(1);
    expect(ligneReprise($second, $herite)['nouvel_etat'])->toContain('Déjà repris');
    expect(MouvementCarriere::count())->toBe($mouvements);
});

it('n\'écrit rien en simulation et produit le rapport CSV', function () {
    interimHerite();

    $this->artisan('interims:reprise --dry-run')->assertSuccessful();

    expect(InterimInterne::count())->toBe(0);
    $fichiers = Storage::disk('local')->files('interims/rapports');
    expect($fichiers)->toHaveCount(1);
    expect($fichiers[0])->toEndWith('-simulation.csv');
    expect(Storage::disk('local')->get($fichiers[0]))->toContain('ancien_etat;nouvel_etat;anomalie')->toContain('[simulation]');
});

it('laisse au rapport un motif ambigu sans le reprendre', function () {
    $herite = interimHerite(['motif' => 'Remplacement temporaire d\'un collègue en congé']);

    $rapport = app(RepriseInterims::class)->executer(false);

    expect(InterimInterne::count())->toBe(0);
    expect(ligneReprise($rapport, $herite)['anomalie'])->toContain('ambigu');
});

it('laisse au rapport un intérim sur un poste de catégorie égale ou inférieure', function () {
    $herite = interimHerite(['poste_cible_id' => $this->posteA->id]);

    $rapport = app(RepriseInterims::class)->executer(false);

    expect(InterimInterne::count())->toBe(0);
    expect(ligneReprise($rapport, $herite)['anomalie'])->toContain('strictement supérieure');
});

it('reprend à régulariser un intérim en cours hors des règles de durée, sans le clôturer', function () {
    $herite = interimHerite(['date_fin_prevue' => '2026-12-31']);

    $rapport = app(RepriseInterims::class)->executer(false);
    $interim = InterimInterne::sole();

    expect($interim->statut)->toBe(StatutInterim::A_REGULARISER);
    expect($interim->date_fin_reelle)->toBeNull();
    expect(ligneReprise($rapport, $herite)['anomalie'])->toContain('ne permet pas de prolongation');
});

it('reprend comme clos un intérim terminé', function () {
    interimHerite(['statut' => StatutMouvement::TERMINE->value, 'date_fin_reelle' => '2026-10-15']);

    app(RepriseInterims::class)->executer(false);
    $interim = InterimInterne::sole();

    expect($interim->statut)->toBe(StatutInterim::CLOS);
    expect($interim->date_fin_reelle->toDateString())->toBe('2026-10-15');
});

it('signale sans les reprendre les mises à disposition et les contrats d\'intérim', function () {
    MouvementCarriere::factory()->effectif()->create([
        'entreprise_id' => 1, 'salarie_id' => $this->salarie->id,
        'type_mouvement' => TypeMouvement::MISE_A_DISPOSITION->value,
    ]);
    Contrat::withoutGlobalScopes()->first()->forceFill(['type_contrat' => 'Intérim'])->save();

    $rapport = app(RepriseInterims::class)->executer(true);
    $anomalies = collect($rapport->anomalies())->pluck('anomalie')->join(' ');

    expect($anomalies)->toContain('module C')->toContain('effectif');
    expect(InterimInterne::count())->toBe(0);
});

it('retire de la liste des intérims non repris ceux qui ont été repris', function () {
    $herite = interimHerite();
    app(RepriseInterims::class)->executer(false);

    $this->get(route('carriere.interims.index'))
        ->assertOk()
        ->assertViewHas('nonRepris', fn ($liste) => ! $liste->contains('id', $herite->id));
});

// --- Écarts de paie sur les périodes validées ---

it('calcule les rappels et trop-perçus d\'indemnité sur les périodes validées, sans écriture', function () {
    // Barème CCIT : M1-A = 103 500 FCFA ; salaire de base au bulletin : 71 500 FCFA.
    $ccit = ReferentielClassification::create(['entreprise_id' => 1, 'code' => 'CCIT', 'nom' => 'CCIT', 'niveau_source' => 'ccit', 'actif' => true]);
    $echelon = EchelonClassification::create(['referentiel_id' => $ccit->id, 'code' => 'A', 'libelle' => 'A', 'ordre' => 1]);
    $e4 = CategorieClassification::create(['referentiel_id' => $ccit->id, 'code' => 'E4', 'libelle' => 'E4', 'ordre' => 4]);
    $m1 = CategorieClassification::create(['referentiel_id' => $ccit->id, 'code' => 'M1', 'libelle' => 'M1', 'ordre' => 10]);
    $positionE4 = PositionClassification::create(['referentiel_id' => $ccit->id, 'code' => 'E4-A', 'categorie_id' => $e4->id, 'echelon_id' => $echelon->id, 'salaire_minimum' => 71500, 'actif' => true]);
    PositionClassification::create(['referentiel_id' => $ccit->id, 'code' => 'M1-A', 'categorie_id' => $m1->id, 'echelon_id' => $echelon->id, 'salaire_minimum' => 103500, 'actif' => true]);
    SituationCarriere::where('salarie_id', $this->salarie->id)->update(['position_classification_id' => $positionE4->id]);
    $this->posteB->update(['categorie_classification_id' => $m1->id]);

    $validee = PeriodePaie::where('statut', 'validated')->firstOrFail();
    $debut = Carbon::create($validee->annee, $validee->mois, 1)->subMonthsNoOverflow(2);
    nouvelInterimTache(MotifInterim::MALADIE_ACCIDENT, $debut->toDateString(), $debut->copy()->addMonths(5)->toDateString());

    $bulletin = BulletinPaie::create([
        'entreprise_id' => 1, 'periode_id' => $validee->id, 'salarie_id' => $this->salarie->id, 'calcule_le' => now(),
    ]);
    $bulletin->lignes()->create(['entreprise_id' => 1, 'code' => 'SAL-BASE', 'libelle' => 'Salaire de base', 'nature' => 'gain', 'montant' => 71500]);
    // Le salaire de base actuel diffère : le contrôle retient celui du bulletin du mois.
    ElementPaieSalarie::where('salarie_id', $this->salarie->id)->whereHas('rubrique', fn ($q) => $q->where('code', 'SAL-BASE'))->update(['montant' => 90000]);
    $saisiesAvant = SaisiePaie::count();

    $lignes = app(ControleEcartsPaieInterims::class)->controler();
    expect($lignes)->toHaveCount(1);
    expect($lignes[0])->toMatchArray(['du' => 32000, 'verse' => 0, 'ecart' => 32000, 'nature' => 'rappel']);

    $bulletin->lignes()->create(['entreprise_id' => 1, 'code' => 'IND-INTERIM', 'libelle' => 'Indemnité d\'intérim', 'nature' => 'gain', 'montant' => 40000]);
    $lignes = app(ControleEcartsPaieInterims::class)->controler();
    expect($lignes[0])->toMatchArray(['du' => 32000, 'verse' => 40000, 'ecart' => -8000, 'nature' => 'trop-perçu']);

    expect(SaisiePaie::count())->toBe($saisiesAvant);
    expect($validee->fresh()->statut->value)->toBe('validated');

    $this->artisan('interims:ecarts-paie')->assertSuccessful();
    expect(Storage::disk('local')->files('interims/rapports'))->toHaveCount(1);
});
