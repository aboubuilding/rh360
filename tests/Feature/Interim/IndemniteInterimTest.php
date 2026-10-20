<?php

/*
| Lot 3 — paie de l'intérim interne : indemnité à partir du 3e mois (RG-INT-06),
| prorata (RG-INT-10), avantages manuels (RG-INT-07), blocage de validation (RG-INT-09).
| Barème de test : Annexe II CCIT 2026, E4-A = 71 500 FCFA, M1-A = 103 500 FCFA.
*/

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Carriere\Models\SituationCarriere;
use App\Domain\Classification\Models\CategorieClassification;
use App\Domain\Classification\Models\EchelonClassification;
use App\Domain\Classification\Models\PositionClassification;
use App\Domain\Classification\Models\ReferentielClassification;
use App\Domain\Interim\Enums\CauseFinInterim;
use App\Domain\Interim\Enums\MotifInterim;
use App\Domain\Interim\Models\InterimInterne;
use App\Domain\Interim\Models\ParametreInterim;
use App\Domain\Interim\Services\IndemniteInterimCalculator;
use App\Domain\Interim\Services\InterimInterneService;
use App\Domain\Interim\Services\PreparateurPaieInterims;
use App\Domain\Organisation\Models\Poste;
use App\Domain\Paie\Actions\ValiderPeriode;
use App\Domain\Paie\Models\BulletinPaie;
use App\Domain\Paie\Models\ElementPaieSalarie;
use App\Domain\Paie\Models\PeriodePaie;
use App\Domain\Paie\Models\RubriquePaie;
use App\Domain\Paie\Models\SaisiePaie;
use App\Domain\Paie\Services\MoteurPaie;
use App\Domain\Personnel\Models\Salarie;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed();
    $this->actingAs(Utilisateur::where('role', Utilisateur::ROLE_SUPER_ADMIN)->first());
    Storage::fake('local');

    // Barème CCIT 2026 (extrait) : référentiel de niveau « ccit ».
    $ccit = ReferentielClassification::create([
        'entreprise_id' => 1, 'code' => 'CCIT-2026', 'nom' => 'CCIT 2026 — Annexe II', 'niveau_source' => 'ccit', 'actif' => true,
    ]);
    $echelonA = EchelonClassification::create(['referentiel_id' => $ccit->id, 'code' => 'A', 'libelle' => 'A', 'ordre' => 1]);
    $this->e4 = CategorieClassification::create(['referentiel_id' => $ccit->id, 'code' => 'E4', 'libelle' => 'E4', 'ordre' => 4]);
    $this->m1 = CategorieClassification::create(['referentiel_id' => $ccit->id, 'code' => 'M1', 'libelle' => 'M1', 'ordre' => 10]);
    $positionE4 = PositionClassification::create([
        'referentiel_id' => $ccit->id, 'code' => 'E4-A', 'categorie_id' => $this->e4->id,
        'echelon_id' => $echelonA->id, 'salaire_minimum' => 71500, 'actif' => true,
    ]);
    PositionClassification::create([
        'referentiel_id' => $ccit->id, 'code' => 'M1-A', 'categorie_id' => $this->m1->id,
        'echelon_id' => $echelonA->id, 'salaire_minimum' => 103500, 'actif' => true,
    ]);

    $this->salarie = Salarie::orderBy('id')->firstOrFail();
    $this->titulaire = Salarie::where('id', '!=', $this->salarie->id)->orderBy('id')->firstOrFail();
    SituationCarriere::where('salarie_id', $this->salarie->id)->update(['position_classification_id' => $positionE4->id]);
    $this->definirSalaireBase = fn (int $montant) => ElementPaieSalarie::updateOrCreate(
        ['salarie_id' => $this->salarie->id, 'rubrique_id' => RubriquePaie::where('code', 'SAL-BASE')->value('id')],
        ['entreprise_id' => 1, 'montant' => $montant, 'actif' => true, 'etat' => 1],
    );
    ($this->definirSalaireBase)(71500);

    $this->poste = Poste::where('intitule', 'Gestionnaire RH')->firstOrFail();
    $this->poste->update(['categorie_classification_id' => $this->m1->id]);

    $this->service = app(InterimInterneService::class);
    $this->calculateur = app(IndemniteInterimCalculator::class);
});

function interimMaladie(string $debut, string $fin): InterimInterne
{
    return test()->service->creer([
        'salarie_id' => test()->salarie->id,
        'poste_occupe_id' => test()->poste->id,
        'titulaire_id' => test()->titulaire->id,
        'motif' => MotifInterim::MALADIE_ACCIDENT->value,
        'date_debut' => $debut,
        'date_fin_prevue' => $fin,
    ]);
}

function periodeOuverte(): PeriodePaie
{
    return PeriodePaie::where('statut', 'open')->orderByDesc('annee')->orderByDesc('mois')->firstOrFail();
}

// --- RG-INT-06 ---

it('RG-INT-06 : calcule l\'exemple E4-A en intérim d\'un poste M1 à 32 000 FCFA par mois', function () {
    $interim = interimMaladie('2026-08-01', '2026-12-31');

    expect($this->calculateur->montantMensuel($interim, Carbon::parse('2026-10-01')))->toBe(32000);
    expect($this->calculateur->pourMois($interim, 2026, 10)->montant)->toBe(32000);
});

it('RG-INT-06 : ramène à 0 une différence négative', function () {
    ($this->definirSalaireBase)(120000);
    $interim = interimMaladie('2026-08-01', '2026-12-31');

    expect($this->calculateur->montantMensuel($interim, Carbon::parse('2026-10-01')))->toBe(0);
    expect($this->calculateur->pourMois($interim, 2026, 10)->montant)->toBe(0);
});

it('RG-INT-02 / RG-INT-06 : ne verse rien les deux premiers mois et commence le 1er jour du 3e mois', function () {
    $interim = interimMaladie('2026-08-01', '2026-12-31');

    expect($this->calculateur->debutIndemnite($interim)->toDateString())->toBe('2026-10-01');
    expect($this->calculateur->pourMois($interim, 2026, 8))->toBeNull();
    expect($this->calculateur->pourMois($interim, 2026, 9))->toBeNull();
    expect($this->calculateur->pourMois($interim, 2026, 10)->estProratisee())->toBeFalse();
});

it('RG-INT-06 : ne verse rien pour un intérim de deux mois au plus', function () {
    $interim = interimMaladie('2026-08-01', '2026-09-30');

    expect($this->calculateur->pourMois($interim, 2026, 10))->toBeNull();
});

it('RG-INT-06 : lit le minimum du barème en vigueur à la date (barème versionné)', function () {
    PositionClassification::where('code', 'M1-A')->update(['fin_effet' => '2026-10-31']);
    $m1 = CategorieClassification::where('code', 'M1')->first();
    PositionClassification::create([
        'referentiel_id' => $m1->referentiel_id, 'code' => 'M1-A-2026-11', 'categorie_id' => $m1->id,
        'echelon_id' => EchelonClassification::where('referentiel_id', $m1->referentiel_id)->where('code', 'A')->value('id'),
        'salaire_minimum' => 105000, 'debut_effet' => '2026-11-01', 'actif' => true,
    ]);
    $interim = interimMaladie('2026-08-01', '2026-12-31');

    expect($this->calculateur->pourMois($interim, 2026, 10)->montant)->toBe(32000);
    expect($this->calculateur->pourMois($interim, 2026, 11)->montant)->toBe(33500);
});

// --- RG-INT-10 (prorata) ---

it('RG-INT-10 : proratise sur les jours réels du mois de début de l\'indemnité', function () {
    $interim = interimMaladie('2026-08-19', '2026-12-31');
    $octobre = $this->calculateur->pourMois($interim, 2026, 10);

    // Indemnité à partir du 19/10 : 13 jours sur 31.
    expect($octobre->du->toDateString())->toBe('2026-10-19');
    expect([$octobre->jours, $octobre->joursBase])->toBe([13, 31]);
    expect($octobre->montant)->toBe((int) round(32000 * 13 / 31));
});

it('RG-INT-10 : proratise le dernier mois à la fin de l\'intérim', function () {
    $interim = interimMaladie('2026-08-01', '2027-01-31');
    $this->service->cloturer($interim, CauseFinInterim::RETOUR_TITULAIRE, '2026-12-10');

    $decembre = $this->calculateur->pourMois($interim->fresh(), 2026, 12);

    expect([$decembre->jours, $decembre->joursBase])->toBe([10, 31]);
    expect($decembre->montant)->toBe((int) round(32000 * 10 / 31));
    expect($this->calculateur->pourMois($interim->fresh(), 2027, 1))->toBeNull();
});

it('RG-INT-10 : applique la base 30 lorsque le paramètre la retient', function () {
    ParametreInterim::create(['entreprise_id' => 1, 'valeurs' => ['prorata_indemnite' => 'base_30']]);
    $interim = interimMaladie('2026-08-19', '2026-12-31');
    $calculateur = app(IndemniteInterimCalculator::class);

    expect($calculateur->pourMois($interim, 2026, 10)->montant)->toBe((int) round(32000 * 13 / 30));
    expect($calculateur->pourMois($interim, 2026, 11)->montant)->toBe(32000);
});

// --- Intégration paie ---

it('verse l\'indemnité dans le bulletin de la période ouverte, sans toucher au salaire de base', function () {
    $periode = periodeOuverte();
    $debutMois = Carbon::create($periode->annee, $periode->mois, 1);
    interimMaladie($debutMois->copy()->subMonthsNoOverflow(2)->toDateString(), $debutMois->copy()->addMonths(2)->toDateString());

    app(MoteurPaie::class)->calculer($periode);
    $periode->update(['statut' => 'open']);
    app(MoteurPaie::class)->calculer($periode);

    $saisies = SaisiePaie::where('periode_id', $periode->id)->where('salarie_id', $this->salarie->id)->where('source', 'interim')->get();
    expect($saisies)->toHaveCount(1);
    expect((int) $saisies->first()->montant)->toBe(32000);
    expect($saisies->first()->observations)->not->toContain('art.');

    $bulletin = BulletinPaie::where('periode_id', $periode->id)->where('salarie_id', $this->salarie->id)->sole();
    $lignes = $bulletin->lignes()->get();
    expect((float) $lignes->firstWhere('code', 'IND-INTERIM')->montant)->toBe(32000.0);
    expect((float) $lignes->firstWhere('code', 'SAL-BASE')->montant)->toBe(71500.0);
});

it('RG-INT-07 : calcule la prime d\'ancienneté sur le salaire de base, hors indemnité', function () {
    $periode = periodeOuverte();
    $prime = function () use ($periode) {
        $bulletin = BulletinPaie::where('periode_id', $periode->id)->where('salarie_id', $this->salarie->id)->sole();

        return (float) $bulletin->lignes()->where('code', 'like', '%ANC%')->sum('montant');
    };

    app(MoteurPaie::class)->calculer($periode);
    $sansInterim = $prime();

    $debutMois = Carbon::create($periode->annee, $periode->mois, 1);
    interimMaladie($debutMois->copy()->subMonthsNoOverflow(2)->toDateString(), $debutMois->copy()->addMonths(2)->toDateString());
    $periode->update(['statut' => 'open']);
    app(MoteurPaie::class)->calculer($periode);

    expect($prime())->toBe($sansInterim);
});

it('RG-INT-07 : verse les avantages convenus du poste occupé, proratisés sur les jours couverts', function () {
    $periode = periodeOuverte();
    $debutMois = Carbon::create($periode->annee, $periode->mois, 1);
    $interim = interimMaladie($debutMois->copy()->subMonthsNoOverflow(2)->toDateString(), $debutMois->copy()->addMonths(2)->toDateString());
    $rubrique = RubriquePaie::where('code', 'PRIME-LOGT')->firstOrFail();
    $this->service->ajouterAvantage($interim, [
        'rubrique_id' => $rubrique->id,
        'montant_mensuel' => 31000,
        'date_debut' => $debutMois->copy()->addDays(15)->toDateString(),
        'reference_accord' => 'ACC-01',
    ], UploadedFile::fake()->create('accord.pdf', 10, 'application/pdf'));

    $rapport = app(PreparateurPaieInterims::class)->preparer($periode);

    $jours = $debutMois->daysInMonth - 15;
    $saisie = SaisiePaie::where('periode_id', $periode->id)->where('rubrique_id', $rubrique->id)->where('salarie_id', $this->salarie->id)->sole();
    expect((int) $saisie->montant)->toBe((int) round(31000 * $jours / $debutMois->daysInMonth));
    expect($saisie->observations)->toContain('ACC-01');
    expect($rapport->anomalies)->toBe([]);
});

it('ne remplace jamais une saisie manuelle sur la rubrique d\'indemnité et le signale', function () {
    $periode = periodeOuverte();
    $debutMois = Carbon::create($periode->annee, $periode->mois, 1);
    interimMaladie($debutMois->copy()->subMonthsNoOverflow(2)->toDateString(), $debutMois->copy()->addMonths(2)->toDateString());
    $rubrique = app(PreparateurPaieInterims::class)->rubriqueIndemnite(1);
    SaisiePaie::create([
        'entreprise_id' => 1, 'periode_id' => $periode->id, 'salarie_id' => $this->salarie->id,
        'rubrique_id' => $rubrique->id, 'montant' => 5000,
    ]);

    $rapport = app(PreparateurPaieInterims::class)->preparer($periode);

    expect((int) SaisiePaie::where('periode_id', $periode->id)->where('rubrique_id', $rubrique->id)->sole()->montant)->toBe(5000);
    expect($rapport->anomalies[0]['message'])->toContain('saisie manuelle');
});

it('signale une indemnité incalculable faute de barème, sans rien verser', function () {
    PositionClassification::where('code', 'M1-A')->delete();
    $periode = periodeOuverte();
    $debutMois = Carbon::create($periode->annee, $periode->mois, 1);
    interimMaladie($debutMois->copy()->subMonthsNoOverflow(2)->toDateString(), $debutMois->copy()->addMonths(2)->toDateString());

    $rapport = app(PreparateurPaieInterims::class)->preparer($periode);

    expect($rapport->saisies)->toBe([]);
    expect($rapport->anomalies[0]['message'])->toContain('Barème introuvable');
    $this->get(route('paie.periodes.show', $periode))->assertOk()->assertSee('Barème introuvable');
});

it('ne modifie jamais une période validée', function () {
    $validee = PeriodePaie::where('statut', 'validated')->firstOrFail();
    $avant = SaisiePaie::where('periode_id', $validee->id)->count();
    interimMaladie('2026-01-01', '2026-06-30');

    expect(fn () => app(PreparateurPaieInterims::class)->preparer($validee))->toThrow(DomainException::class);
    expect(SaisiePaie::where('periode_id', $validee->id)->count())->toBe($avant);
});

// --- RG-INT-09 ---

it('RG-INT-09 : bloque la validation de la paie tant qu\'un intérim est à régulariser', function () {
    $periode = periodeOuverte();
    app(MoteurPaie::class)->calculer($periode);
    $interim = interimMaladie('2026-08-01', '2026-12-31');
    $this->service->passerARegulariser($interim, 'Délai expiré sans décision');

    expect(fn () => app(ValiderPeriode::class)->executer($periode->fresh()))
        ->toThrow(DomainException::class, 'Validation de la paie bloquée');
    expect($periode->fresh()->estFigee())->toBeFalse();
    $this->get(route('paie.periodes.show', $periode))->assertOk()->assertSee('Validation bloquée');
});

it('RG-INT-09 : laisse valider la paie si l\'entreprise a désactivé le blocage', function () {
    ParametreInterim::create(['entreprise_id' => 1, 'valeurs' => ['bloquer_cloture_paie_si_a_regulariser' => false]]);
    $periode = periodeOuverte();
    app(MoteurPaie::class)->calculer($periode);
    $this->service->passerARegulariser(interimMaladie('2026-08-01', '2026-12-31'), 'Délai expiré sans décision');

    app(ValiderPeriode::class)->executer($periode->fresh());

    expect($periode->fresh()->estFigee())->toBeTrue();
});
