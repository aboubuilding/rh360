<?php

/*
| Congés payés — grand livre par lot annuel : acquisition mensuelle avec périodes assimilées,
| ouverture après 12 mois et anticipation après 6 mois, report limité à 2 ans et prise sur le
| lot le plus ancien, reprise des soldes existants, rappel pendant le congé, ordre de départ,
| décalage d'une date fixée et allocation de congé (Code art. 200 à 202 ; CCIT art. 49).
*/

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Conges\Enums\QualificationAbsence;
use App\Domain\Conges\Enums\StatutDemandeConge;
use App\Domain\Conges\Models\Absence;
use App\Domain\Conges\Models\DemandeConge;
use App\Domain\Conges\Models\MouvementConge;
use App\Domain\Conges\Models\ParametreDemande;
use App\Domain\Conges\Models\SoldeConge;
use App\Domain\Conges\Models\TypeConge;
use App\Domain\Conges\Services\GrandLivreConges;
use App\Domain\Paie\Models\BulletinPaie;
use App\Domain\Paie\Models\LigneBulletin;
use App\Domain\Paie\Models\PeriodePaie;
use App\Domain\Personnel\Models\Salarie;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-10-01 09:00'));
    $this->seed();
    $this->actingAs(Utilisateur::where('role', Utilisateur::ROLE_SUPER_ADMIN)->first());

    // Salarié sans historique de congés.
    $this->salarie = Salarie::orderBy('id')->firstOrFail();
    DB::table('demandes_conges')->where('salarie_id', $this->salarie->id)->delete();
    DB::table('absences')->where('salarie_id', $this->salarie->id)->delete();
    DB::table('soldes_conges')->where('salarie_id', $this->salarie->id)->delete();

    $this->type = fn (string $code) => TypeConge::where('code', $code)->sole();
    $this->ca = ($this->type)('CA');
    $this->grandLivre = fn () => app(GrandLivreConges::class);
    $this->lot = fn (int $lot, ?string $date = null) => ($this->grandLivre)()->lots($this->salarie->fresh(), $this->ca, $date ? Carbon::parse($date) : null)->firstWhere('lot', $lot);
    $this->acquerir = fn (string $jusqua) => ($this->grandLivre)()->acquerirJusqua($this->salarie->fresh(), $this->ca, Carbon::parse($jusqua));

    // Demande accordée d'un autre type (maladie, permission…), qui compte dans le décompte.
    $this->absenceAccordee = fn (string $code, string $debut, string $reprise) => DemandeConge::factory()->create([
        'entreprise_id' => 1, 'salarie_id' => $this->salarie->id, 'type_conge_id' => ($this->type)($code)->id,
        'statut' => StatutDemandeConge::AUTORISEE->value, 'date_debut' => $debut, 'date_reprise' => $reprise,
        'duree_jours' => Carbon::parse($debut)->diffInDays(Carbon::parse($reprise)),
    ]);

    // Congé payé par l'écran : création, soumission puis autorisation.
    $this->conge = function (string $debut, string $reprise, array $autres = [], array $autorisation = []) {
        $this->post(route('conges.demandes.store'), [
            'salarie_id' => $this->salarie->id, 'type_conge_id' => $this->ca->id, 'date_debut' => $debut, 'date_reprise' => $reprise,
        ] + $autres)->assertSessionHasNoErrors();
        $demande = DemandeConge::latest('id')->firstOrFail();
        $this->postJson(route('conges.demandes.soumettre', $demande))->assertOk();

        return [$demande, $this->postJson(route('conges.demandes.autoriser', $demande), $autorisation)];
    };
});

// --- Acquisition ---

it('acquiert 30 jours sur l\'année malgré une maladie, une permission et une grève', function () {
    $this->salarie->update(['date_embauche' => '2026-01-01']);
    ($this->absenceAccordee)('MAL', '2026-02-02', '2026-02-14');
    ($this->absenceAccordee)('PERM-EVT', '2026-05-04', '2026-05-08');
    Absence::create(['entreprise_id' => 1, 'salarie_id' => $this->salarie->id, 'type_conge_id' => ($this->type)('GREVE')->id,
        'debut_le' => '2026-06-08 00:00:00', 'fin_le' => '2026-06-10 23:59:59', 'statut' => 'constatée',
        'qualification' => QualificationAbsence::JUSTIFIEE->value, 'statut_transmission_paie' => 'a_preparer', 'etat' => 1]);

    expect(($this->acquerir)('2026-12-31'))->toBe(30.0);
    // Idempotent : une seconde passe n'acquiert rien.
    expect(($this->acquerir)('2026-12-31'))->toBe(0.0);
});

it('réduit l\'acquisition au-delà de 6 mois de maladie et pour une absence non justifiée', function () {
    $this->salarie->update(['date_embauche' => '2026-01-01']);
    // Maladie continue du 1er janvier au 31 août : juillet et août ne sont plus assimilés.
    ($this->absenceAccordee)('MAL', '2026-01-01', '2026-09-01');

    expect(($this->acquerir)('2026-12-31'))->toBe(25.0);

    // Absence non justifiée de 3 jours en mars d'une autre année : 2,5 × 28/31.
    $this->salarie->update(['date_embauche' => '2025-01-01']);
    Absence::create(['entreprise_id' => 1, 'salarie_id' => $this->salarie->id, 'type_conge_id' => ($this->type)('ABS-NJ')->id,
        'debut_le' => '2025-03-10 00:00:00', 'fin_le' => '2025-03-12 23:59:59', 'statut' => 'constatée',
        'qualification' => QualificationAbsence::INJUSTIFIEE->value, 'statut_transmission_paie' => 'a_preparer', 'etat' => 1]);
    expect(($this->grandLivre)()->acquerirMois($this->salarie->fresh(), $this->ca, Carbon::parse('2025-03-01')))->toBe(2.26);
});

it('acquiert au prorata de l\'embauche et arrondit le lot à la demi-journée supérieure à son ouverture', function () {
    // Embauche le 15 mars : 17/31 de mars puis 9 mois complets = 23,87 jours.
    $this->salarie->update(['date_embauche' => '2026-03-15']);
    ($this->acquerir)('2026-12-31');
    expect(($this->lot)(2026, '2026-12-31')['acquis'])->toBe(23.87);

    $this->travelTo(Carbon::parse('2027-03-16 08:00'));
    ($this->grandLivre)()->cloturerLots($this->salarie->fresh(), $this->ca);
    expect(($this->lot)(2026)['acquis'])->toBe(24.0);
    expect(($this->lot)(2026)['ouvert_le']->toDateString())->toBe('2027-03-15');
});

// --- Ouverture du droit et anticipation ---

it('refuse un congé avant 6 mois de service, puis ne l\'accorde par anticipation qu\'avec l\'accord', function () {
    $this->travelTo(Carbon::parse('2026-08-01 08:00'));
    $this->salarie->update(['date_embauche' => '2026-03-01']);
    ($this->acquerir)('2026-07-31');

    $this->post(route('conges.demandes.store'), ['salarie_id' => $this->salarie->id, 'type_conge_id' => $this->ca->id, 'date_debut' => '2026-08-17', 'date_reprise' => '2026-08-24']);
    $this->postJson(route('conges.demandes.soumettre', DemandeConge::latest('id')->first()))
        ->assertStatus(422)->assertJsonFragment(['message' => 'Le droit au congé payé s\'ouvre après 6 mois de service consécutifs, soit à partir du 01/09/2026.']);

    // Après 6 mois : 12,5 jours acquis à fin juillet, utilisables seulement par anticipation.
    [$demande, $reponse] = ($this->conge)('2026-10-05', '2026-10-15');
    $reponse->assertStatus(422)->assertJsonFragment(['message' => 'Solde insuffisant. Disponible : 0.00 jours, demandé : 10.00 jours. Les jours acquis sur l\'année en cours peuvent être pris par anticipation, si vous l\'accordez.']);

    $this->postJson(route('conges.demandes.autoriser', $demande), ['anticipation' => 1])->assertOk();
    $demande->refresh();
    expect([$demande->statut, $demande->anticipation])->toBe([StatutDemandeConge::AUTORISEE, true]);
    expect(($this->lot)(2026)['reste'])->toBe(2.5);
});

// --- Report et lots ---

it('impute les prises au lot le plus ancien et limite le report à deux ans', function () {
    $this->salarie->update(['date_embauche' => '2024-01-01']);

    $this->travelTo(Carbon::parse('2025-03-01 08:00'));
    ($this->acquerir)('2025-02-28');
    [$mars] = ($this->conge)('2025-03-10', '2025-03-20');
    expect($mars->fresh()->statut)->toBe(StatutDemandeConge::AUTORISEE);

    // Au 2 janvier 2026 : 20 jours du lot 2024 et 30 du lot 2025.
    $this->travelTo(Carbon::parse('2026-01-02 08:00'));
    ($this->acquerir)('2025-12-31');
    expect(($this->grandLivre)()->disponible($this->salarie->fresh(), $this->ca))->toBe(50.0);

    // Au 2 janvier 2027, le lot 2026 s'ouvre : le reliquat du lot 2024 dépasse les 2 ans.
    $this->travelTo(Carbon::parse('2027-01-02 08:00'));
    ($this->acquerir)('2026-12-31');
    expect(($this->lot)(2024)['expire'])->toBeTrue();
    expect(($this->grandLivre)()->disponible($this->salarie->fresh(), $this->ca))->toBe(60.0);

    // Par défaut, le reliquat est signalé pour un départ imposé ; avec « perte », il expire.
    $resultat = ($this->grandLivre)()->cloturerLots($this->salarie->fresh(), $this->ca);
    expect($resultat['a_imposer'])->toHaveCount(1)->and($resultat['a_imposer'][0]['reste'])->toBe(20.0);
    expect(MouvementConge::where('nature', MouvementConge::EXPIRATION)->exists())->toBeFalse();

    ParametreDemande::create(['entreprise_id' => 1, 'valeurs' => ['conge_lot_expire' => 'perte']]);
    app()->forgetScopedInstances();
    expect(($this->grandLivre)()->cloturerLots($this->salarie->fresh(), $this->ca)['expirations'])->toBe(1);
    expect(($this->lot)(2024)['reste'])->toBe(0.0);
    expect(SoldeConge::where('salarie_id', $this->salarie->id)->where('annee', 2024)->sole()->disponible)->toBe(0.0);
});

it('prend 35 jours sur les lots 2024 puis 2025', function () {
    $this->salarie->update(['date_embauche' => '2024-01-01']);
    $this->travelTo(Carbon::parse('2026-06-01 08:00'));
    ($this->acquerir)('2026-05-31');
    ($this->grandLivre)()->ajuster($this->salarie->fresh(), $this->ca, 2024, -10, 'Congé pris en 2025');

    [$juillet] = ($this->conge)('2026-07-01', '2026-08-05');
    expect($juillet->fresh()->statut)->toBe(StatutDemandeConge::AUTORISEE);

    $prises = MouvementConge::where('demande_id', $juillet->id)->where('nature', MouvementConge::PRISE)->orderBy('lot')->pluck('jours', 'lot')->map(fn ($j) => (float) $j)->all();
    expect($prises)->toBe([2024 => -20.0, 2025 => -15.0]);

    // Annulation : les jours reviennent sur leurs lots.
    $this->postJson(route('conges.demandes.annuler', $juillet), ['motif' => 'Report à la demande du salarié'])->assertOk();
    expect([($this->lot)(2024)['reste'], ($this->lot)(2025)['reste']])->toBe([20.0, 30.0]);
});

it('reprend les soldes existants sans double compte', function () {
    $this->salarie->update(['date_embauche' => '2020-01-01']);
    $ancienne = DemandeConge::factory()->create([
        'entreprise_id' => 1, 'salarie_id' => $this->salarie->id, 'type_conge_id' => $this->ca->id,
        'statut' => StatutDemandeConge::REPRISE_CONFIRMEE->value, 'date_debut' => '2025-08-04', 'date_reprise' => '2025-08-14', 'duree_jours' => 10,
    ]);
    SoldeConge::factory()->create(['entreprise_id' => 1, 'salarie_id' => $this->salarie->id, 'type_conge_id' => $this->ca->id, 'annee' => 2025, 'solde_ouverture' => 0, 'acquis' => 30, 'ajustement' => 0, 'consomme' => 10, 'reserve' => 0]);
    SoldeConge::factory()->create(['entreprise_id' => 1, 'salarie_id' => $this->salarie->id, 'type_conge_id' => $this->ca->id, 'annee' => 2026, 'solde_ouverture' => 20, 'acquis' => 30, 'ajustement' => 0, 'consomme' => 0, 'reserve' => 0]);

    expect(($this->grandLivre)()->disponible($this->salarie->fresh(), $this->ca))->toBe(50.0);
    expect(MouvementConge::where('demande_id', $ancienne->id)->value('lot'))->toBe(2025);

    // Les mois de 2026 déjà couverts par la reprise ne sont pas acquis une seconde fois.
    expect(($this->acquerir)('2026-09-30'))->toBe(0.0);

    ($this->grandLivre)()->projeter($this->salarie->fresh(), $this->ca);
    $projection = SoldeConge::where('salarie_id', $this->salarie->id)->where('type_conge_id', $this->ca->id)->orderBy('annee')->get();
    expect($projection->map(fn ($s) => [$s->annee, (float) $s->solde_ouverture, (float) $s->acquis, (float) $s->consomme, $s->disponible])->all())
        ->toBe([[2025, 0.0, 30.0, 10.0, 20.0], [2026, 0.0, 30.0, 0.0, 30.0]]);
});

it('rend le grand livre non modifiable', function () {
    $this->salarie->update(['date_embauche' => '2026-01-01']);
    ($this->acquerir)('2026-01-31');
    $mouvement = MouvementConge::where('salarie_id', $this->salarie->id)->firstOrFail();

    expect(fn () => $mouvement->update(['jours' => 5]))->toThrow(LogicException::class);
    expect(fn () => $mouvement->delete())->toThrow(LogicException::class);
});

// --- Départ ---

it('exige un ordre de départ 15 jours avant pour un départ fixé par l\'employeur', function () {
    $this->salarie->update(['date_embauche' => '2020-01-01']);
    ($this->acquerir)('2026-09-30');

    [$fixe] = ($this->conge)('2026-10-11', '2026-10-21', ['initiative' => 'employeur']);
    $this->postJson(route('conges.demandes.programmer', $fixe))->assertStatus(422)
        ->assertJsonFragment(['message' => 'L\'ordre de départ doit être communiqué au moins 15 jours avant le départ, soit au plus tard le 26/09/2026. Reportez la date de départ ou obtenez l\'accord du salarié sur une demande à son initiative.']);

    ParametreDemande::create(['entreprise_id' => 1, 'valeurs' => ['conge_ordre_depart_tardif' => 'alerter']]);
    app()->forgetScopedInstances();
    $this->postJson(route('conges.demandes.programmer', $fixe))->assertOk()->assertJsonFragment(['message' => 'Demande programmée. L\'ordre de départ doit être communiqué au moins 15 jours avant le départ, soit au plus tard le 26/09/2026.']);
    expect($fixe->fresh()->ordre_depart_le->toDateString())->toBe('2026-10-01');

    // À l'initiative du salarié, pas de délai d'ordre de départ.
    [$demandee] = ($this->conge)('2026-11-02', '2026-11-06');
    $this->postJson(route('conges.demandes.programmer', $demandee))->assertOk()->assertJson(['message' => 'Demande programmée.']);
});

it('ne décale une date fixée de plus de 3 mois qu\'en cas de force majeure', function () {
    $this->salarie->update(['date_embauche' => '2020-01-01']);
    ($this->acquerir)('2026-09-30');
    [$demande] = ($this->conge)('2026-11-02', '2026-11-12');

    $this->postJson(route('conges.demandes.reporter', $demande), ['date_debut' => '2027-03-01', 'date_reprise' => '2027-03-11', 'motif' => 'Charge de travail'])
        ->assertStatus(422)->assertJsonFragment(['message' => 'Une date de départ fixée ne peut être décalée de plus de 3 mois (entre le 02/08/2026 et le 02/02/2027), sauf force majeure.']);

    $this->postJson(route('conges.demandes.reporter', $demande), ['date_debut' => '2026-12-07', 'date_reprise' => '2026-12-17', 'motif' => 'Inventaire de fin d\'année'])->assertOk();
    $demande->refresh();
    expect([$demande->date_debut->toDateString(), $demande->date_debut_fixee->toDateString(), (float) $demande->duree_jours])->toBe(['2026-12-07', '2026-11-02', 10.0]);

    $this->postJson(route('conges.demandes.reporter', $demande), ['date_debut' => '2027-03-01', 'date_reprise' => '2027-03-11', 'motif' => 'Inondation du site', 'force_majeure' => 1])->assertOk();
    expect(MouvementConge::where('demande_id', $demande->id)->sum('jours'))->toEqual(-10);
    expect($demande->historique()->reorder('id', 'desc')->first()->motif)->toContain('force majeure');
});

it('prolonge le congé des jours travaillés lors d\'un rappel', function () {
    $this->salarie->update(['date_embauche' => '2020-01-01']);
    $this->travelTo(Carbon::parse('2026-07-15 08:00'));
    ($this->acquerir)('2026-06-30');
    [$aout] = ($this->conge)('2026-08-01', '2026-08-31');

    $this->travelTo(Carbon::parse('2026-08-01 08:00'));
    $this->post(route('conges.demandes.demarrer', $aout));
    $this->postJson(route('conges.demandes.rappeler', $aout), ['du' => '2026-08-12', 'au' => '2026-08-14', 'motif' => 'Audit des comptes'])->assertOk();

    // Congé jusqu'au 2 septembre inclus, reprise le 3.
    $aout->refresh();
    expect([$aout->date_reprise->toDateString(), (float) $aout->duree_jours])->toBe(['2026-09-03', 30.0]);
    expect($aout->rappels->sole()->jours)->toEqual(3);

    $this->postJson(route('conges.demandes.rappeler', $aout), ['du' => '2026-09-10', 'au' => '2026-09-11', 'motif' => 'Hors congé'])->assertStatus(422);
});

// --- Allocation ---

it('calcule l\'allocation au douzième des 12 derniers mois, hors primes de rendement et frais', function () {
    $this->salarie->update(['date_embauche' => '2020-01-01']);
    $bulletins = BulletinPaie::where('salarie_id', $this->salarie->id)->pluck('id');
    LigneBulletin::withoutGlobalScopes()->whereIn('bulletin_id', $bulletins)->delete();
    BulletinPaie::whereIn('id', $bulletins)->delete();

    // Octobre 2025 à septembre 2026 : base 200 000, ancienneté 20 000 ; une fois 150 000 d'heures
    // supplémentaires, 60 000 de prime de rendement et 40 000 de transport.
    for ($m = Carbon::parse('2025-10-01'); $m->lte(Carbon::parse('2026-09-01')); $m->addMonth()) {
        $periode = PeriodePaie::firstOrCreate(['entreprise_id' => 1, 'annee' => $m->year, 'mois' => $m->month], ['statut' => 'validated', 'etat' => 1]);
        $bulletin = BulletinPaie::factory()->create(['entreprise_id' => 1, 'periode_id' => $periode->id, 'salarie_id' => $this->salarie->id]);
        $lignes = [['SAL-BASE', 200000], ['PRIME-ANC', 20000]];
        if ($m->month === 3) {
            $lignes = [...$lignes, ['HS', 150000], ['PRIME-REND', 60000], ['PRIME-TRANSP', 40000]];
        }
        foreach ($lignes as [$code, $montant]) {
            LigneBulletin::create(['entreprise_id' => 1, 'bulletin_id' => $bulletin->id, 'code' => $code, 'libelle' => $code, 'nature' => 'gain', 'montant' => $montant]);
        }
        LigneBulletin::create(['entreprise_id' => 1, 'bulletin_id' => $bulletin->id, 'code' => 'CNSS', 'libelle' => 'CNSS', 'nature' => 'cotisation', 'montant' => 9000]);
    }
    ($this->acquerir)('2026-09-30');
    [$demande] = ($this->conge)('2026-10-12', '2026-10-24');

    $allocation = app(\App\Domain\Conges\Services\CalculateurAllocationConge::class)->calculer($demande->fresh());
    expect([$allocation['remuneration'], $allocation['mois'], $allocation['allocation_periode'], $allocation['par_jour'], $allocation['montant']])
        ->toBe([2790000, 12, 232500, 7750.0, 93000]);

    $this->get(route('conges.demandes.show', $demande))->assertOk()->assertSee('93 000 FCFA')->assertSee('Droits par année d\'acquisition', false);
});
