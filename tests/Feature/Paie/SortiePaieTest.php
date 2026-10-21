<?php

/*
| Paie de sortie — solde de tout compte : salaire jusqu'à la cessation, préavis dispensé par
| l'employeur, congés acquis non pris, indemnités de licenciement (35 % / 40 % / 45 %, fractions
| comprises), rupture conventionnelle, accompagnement économique, retraite et décès (CCIT
| art. 19 à 26, 70 ; Code art. 77, 82, 83, 95, 102, 165, 201).
| Salaire mensuel de 140 000, salaire global moyen de 140 000, 7 ans et 5 mois d'ancienneté.
*/

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Carriere\Models\SituationCarriere;
use App\Domain\Conges\Models\TypeConge;
use App\Domain\Conges\Services\GrandLivreConges;
use App\Domain\Paie\Models\BulletinPaie;
use App\Domain\Paie\Models\ElementPaieSalarie;
use App\Domain\Paie\Models\PeriodePaie;
use App\Domain\Paie\Models\RegleAnciennete;
use App\Domain\Paie\Models\RubriquePaie;
use App\Domain\Paie\Models\SortieSalarie;
use App\Domain\Paie\Services\CalculateurSoldeToutCompte;
use App\Domain\Paie\Services\MoteurPaie;
use App\Domain\Personnel\Models\Salarie;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-10-20 09:00'));
    $this->seed();
    $this->actingAs(Utilisateur::where('role', Utilisateur::ROLE_SUPER_ADMIN)->first());

    $this->salarie = Salarie::where('actif', true)->orderBy('id')->firstOrFail();
    Salarie::where('actif', true)->where('id', '!=', $this->salarie->id)->update(['actif' => false]);
    RegleAnciennete::query()->update(['actif' => false]);
    foreach (['demandes_conges', 'absences', 'soldes_conges'] as $table) {
        DB::table($table)->where('salarie_id', $this->salarie->id)->delete();
    }
    $this->position = SituationCarriere::where('salarie_id', $this->salarie->id)->first()->positionClassification;
    $this->position->update(['salaire_minimum' => 100000]);
    ElementPaieSalarie::where('salarie_id', $this->salarie->id)->delete();
    ElementPaieSalarie::create(['entreprise_id' => 1, 'salarie_id' => $this->salarie->id, 'rubrique_id' => RubriquePaie::where('code', 'SAL-BASE')->value('id'), 'montant' => 140000, 'actif' => true, 'etat' => 1]);
    $this->salarie->update(['date_embauche' => '2019-05-15']);

    // 12 bulletins précédents à 140 000 de brut.
    for ($m = Carbon::parse('2025-10-01'); $m->lte(Carbon::parse('2026-09-01')); $m->addMonth()) {
        $periode = PeriodePaie::firstOrCreate(['entreprise_id' => 1, 'annee' => $m->year, 'mois' => $m->month], ['statut' => 'validated', 'etat' => 1]);
        BulletinPaie::where('periode_id', $periode->id)->where('salarie_id', $this->salarie->id)->delete();
        BulletinPaie::factory()->create(['entreprise_id' => 1, 'periode_id' => $periode->id, 'salarie_id' => $this->salarie->id, 'montant_brut' => 140000]);
    }
    // 25 jours de congé acquis non pris.
    $conge = TypeConge::where('code', 'CA')->sole();
    $this->travelTo(Carbon::parse('2026-10-01 09:00'));
    app(GrandLivreConges::class)->ajuster($this->salarie->fresh(), $conge, 2025, 25, 'Reprise des droits');
    $this->travelTo(Carbon::parse('2026-10-20 09:00'));

    $this->octobre = PeriodePaie::updateOrCreate(['entreprise_id' => 1, 'annee' => 2026, 'mois' => 10], ['statut' => 'open', 'etat' => 1]);
    $this->sortie = fn (array $valeurs) => SortieSalarie::create($valeurs + [
        'entreprise_id' => 1, 'salarie_id' => $this->salarie->id, 'date_cessation' => '2026-10-15',
        'motif' => 'licenciement_personnel', 'preavis' => 'dispense_employeur', 'preavis_mois' => 3,
    ]);
    $this->lignes = fn (SortieSalarie $s) => collect(app(CalculateurSoldeToutCompte::class)->calculer($s->fresh())['lignes'])
        ->mapWithKeys(fn ($l) => [$l[0] => $l[2]])->all();
});

it('calcule le solde d\'un licenciement après 7 ans et 5 mois, préavis dispensé par l\'employeur', function () {
    $lignes = ($this->lignes)(($this->sortie)([]));

    // 3 mois de préavis ; 25 jours à 140 000 / 30 ; 140 000 × (5 × 35 % + 29/12 × 40 %).
    expect($lignes)->toBe(['PREAVIS-IND' => 420000.0, 'ICC' => 116666.67, 'IND-LIC' => 380333.33]);
});

it('porte le solde sur le bulletin du mois de la cessation, puis n\'émet plus de bulletin', function () {
    ($this->sortie)([]);

    app(MoteurPaie::class)->calculer($this->octobre->fresh());
    $bulletin = BulletinPaie::where('periode_id', $this->octobre->id)->where('salarie_id', $this->salarie->id)->sole();
    $montants = $bulletin->lignes->pluck('montant', 'code')->map(fn ($m) => (float) $m)->all();

    // Salaire du 1er au 15 octobre : 140 000 × 15/31.
    expect($montants['SAL-BASE'])->toBe(67741.94);
    expect([$montants['PREAVIS-IND'], $montants['ICC'], $montants['IND-LIC']])->toBe([420000.0, 116666.67, 380333.33]);
    expect($bulletin->jours_presence)->toBe(15);

    $novembre = PeriodePaie::updateOrCreate(['entreprise_id' => 1, 'annee' => 2026, 'mois' => 11], ['statut' => 'open', 'etat' => 1]);
    app(MoteurPaie::class)->calculer($novembre);
    expect(BulletinPaie::where('periode_id', $novembre->id)->where('salarie_id', $this->salarie->id)->exists())->toBeFalse();
});

it('ne verse ni préavis ni indemnité de licenciement en cas de faute grave, mais paie les congés', function () {
    expect(array_keys(($this->lignes)(($this->sortie)(['faute' => 'grave']))))->toBe(['ICC']);
});

it('ne verse pas de préavis si le salarié a demandé la dispense', function () {
    expect(array_keys(($this->lignes)(($this->sortie)(['preavis' => 'dispense_salarie']))))->toBe(['ICC', 'IND-LIC']);
});

it('porte la rupture conventionnelle au moins à l\'indemnité de licenciement', function () {
    $sortie = ($this->sortie)(['motif' => 'rupture_conventionnelle', 'preavis' => 'sans_objet', 'montant_convenu' => 200000]);

    $calcul = app(CalculateurSoldeToutCompte::class)->calculer($sortie);
    expect(collect($calcul['lignes'])->firstWhere(0, 'IND-RC')[2])->toBe(380333.33);
    expect($calcul['alertes'][0])->toContain('inférieur à l\'indemnité de licenciement');
});

it('ajoute 2 mois d\'accompagnement à un licenciement économique d\'agent d\'exécution, 1 mois pour un cadre', function () {
    $this->position->categorie->update(['code' => 'E4']);
    expect(($this->lignes)(($this->sortie)(['motif' => 'licenciement_economique', 'preavis_mois' => null]))['ACCOMP-ECO'])->toBe(280000.0);

    SortieSalarie::query()->delete();
    $this->position->categorie->update(['code' => 'C2']);
    $lignes = ($this->lignes)(($this->sortie)(['motif' => 'licenciement_economique', 'preavis_mois' => null]));
    expect([$lignes['ACCOMP-ECO'], $lignes['PREAVIS-IND']])->toBe([140000.0, 420000.0]);
});

it('garantit au moins 3 mois de salaire au départ à la retraite', function () {
    $this->salarie->update(['date_embauche' => '2024-10-15']);
    // 2 ans : 45 % de 140 000 × 70 % = 44 100, relevé au minimum de 3 mois.
    expect(($this->lignes)(($this->sortie)(['motif' => 'retraite', 'preavis' => 'sans_objet']))['IND-RETRAITE'])->toBe(420000.0);
});

it('verse aux héritiers l\'indemnité de licenciement et les frais funéraires', function () {
    $lignes = ($this->lignes)(($this->sortie)(['motif' => 'deces', 'preavis' => 'sans_objet']));

    expect([$lignes['IND-DECES'], $lignes['FRAIS-FUNER']])->toBe([380333.33, 140000.0]);
});

it('enregistre une sortie depuis l\'écran et affiche le solde', function () {
    $this->post(route('paie.sorties.store'), [
        'salarie_id' => $this->salarie->id, 'date_cessation' => '2026-10-15', 'motif' => 'licenciement_personnel', 'preavis' => 'dispense_employeur',
    ])->assertRedirect()->assertSessionHas('success');

    $sortie = SortieSalarie::sole();
    $this->get(route('paie.sorties.index', ['sortie' => $sortie->id]))->assertOk()
        ->assertSee('Solde de tout compte')->assertSee('Indemnité de licenciement')->assertSee('380 333 FCFA');
});
