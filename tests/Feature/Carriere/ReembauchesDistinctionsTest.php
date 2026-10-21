<?php

/*
| Réembauches (cumul de l'ancienneté, CCIT art. 40) et distinctions honorifiques (CCIT art. 64).
*/

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Carriere\Enums\MotifDepart;
use App\Domain\Carriere\Models\DistinctionHonorifique;
use App\Domain\Carriere\Models\ParametreAvancement;
use App\Domain\Carriere\Models\PeriodeEmploiAnterieure;
use App\Domain\Carriere\Services\CalculateurAncienneteUtile;
use App\Domain\Carriere\Services\GestionnaireDistinctions;
use App\Domain\Paie\Models\ElementPaieSalarie;
use App\Domain\Paie\Models\PeriodePaie;
use App\Domain\Paie\Models\RegleAnciennete;
use App\Domain\Paie\Models\SaisiePaie;
use App\Domain\Personnel\Models\Salarie;
use Carbon\Carbon;

beforeEach(function () {
    $this->seed();
    $this->actingAs(Utilisateur::where('role', Utilisateur::ROLE_SUPER_ADMIN)->first());
    $this->salarie = Salarie::orderBy('id')->firstOrFail();
    $this->emploi = fn (string $du, string $au, MotifDepart $motif) => PeriodeEmploiAnterieure::create([
        'entreprise_id' => 1, 'salarie_id' => $this->salarie->id, 'date_debut' => $du, 'date_fin' => $au, 'motif_depart' => $motif->value,
    ]);
    // Prime sur le salaire réel, pour des montants simples à vérifier.
    RegleAnciennete::query()->update(['mode_base' => 'salary_base', 'mode_taux' => 'ccit_cumul', 'taux_initial' => 2, 'taux_max' => 35, 'annee_debut_increment' => 4]);
    ElementPaieSalarie::where('salarie_id', $this->salarie->id)->whereHas('rubrique', fn ($q) => $q->where('code', 'SAL-BASE'))->update(['montant' => 150000]);
});

// --- Réembauches ---

it('cumule l\'ancienneté d\'un emploi antérieur quitté pour compression', function () {
    $this->salarie->update(['date_embauche' => '2021-09-01']);
    ($this->emploi)('2015-04-01', '2019-03-31', MotifDepart::COMPRESSION);

    // 4 ans (1 461 jours) + 5 ans
    expect(app(CalculateurAncienneteUtile::class)->annees($this->salarie->fresh(), Carbon::parse('2026-09-01')))->toBe(9);
});

it('ne cumule pas un emploi antérieur quitté par démission', function () {
    $this->salarie->update(['date_embauche' => '2021-09-01']);
    ($this->emploi)('2015-04-01', '2019-03-31', MotifDepart::DEMISSION);

    expect(app(CalculateurAncienneteUtile::class)->annees($this->salarie->fresh(), Carbon::parse('2026-09-01')))->toBe(5);
});

it('arrête le cumul au premier départ d\'un motif non cumulable', function () {
    $this->salarie->update(['date_embauche' => '2021-09-01']);
    ($this->emploi)('2010-01-01', '2012-12-31', MotifDepart::COMPRESSION);
    ($this->emploi)('2013-06-01', '2015-05-31', MotifDepart::DEMISSION);
    ($this->emploi)('2018-01-01', '2019-12-31', MotifDepart::SERVICE_MILITAIRE);

    // Seul l'emploi 2018-2019 (730 jours) est cumulé.
    expect(app(CalculateurAncienneteUtile::class)->joursEmploisAnterieurs($this->salarie->fresh()))->toBe(730);
});

it('enregistre un emploi antérieur depuis la situation de carrière', function () {
    $this->salarie->update(['date_embauche' => '2021-09-01']);

    $this->post(route('carriere.situations.emplois-anterieurs.store', $this->salarie), [
        'date_debut' => '2015-04-01', 'date_fin' => '2019-03-31', 'motif_depart' => MotifDepart::COMPRESSION->value,
    ])->assertRedirect()->assertSessionHasNoErrors();
    $this->post(route('carriere.situations.emplois-anterieurs.store', $this->salarie), [
        'date_debut' => '2021-01-01', 'date_fin' => '2022-01-01', 'motif_depart' => MotifDepart::COMPRESSION->value,
    ])->assertSessionHasErrors('date_fin');

    $this->get(route('carriere.situations.show', $this->salarie))
        ->assertOk()->assertSee('Emplois antérieurs dans l\'entreprise', false)->assertSee('Compression de personnel');
});

// --- Distinctions honorifiques ---

it('crée la distinction à 10 ans avec une gratification d\'un mois de salaire de base et de prime', function () {
    $this->salarie->update(['date_embauche' => today()->subYears(10)->subDay()]);

    expect(app(GestionnaireDistinctions::class)->generer(1))->toBe(1);
    $distinction = DistinctionHonorifique::where('salarie_id', $this->salarie->id)->sole();

    // 10 ans : prime 9 % de 150 000 = 13 500 ; 1 mois × (150 000 + 13 500)
    expect($distinction->palier_annees)->toBe(10);
    expect([$distinction->base, $distinction->prime_anciennete, $distinction->montant])->toBe([150000, 13500, 163500]);
    expect(app(GestionnaireDistinctions::class)->generer(1))->toBe(0);
});

it('applique les paliers de 10 à 35 ans, avec 4 mois au moins à 35 ans', function () {
    $this->salarie->update(['date_embauche' => today()->subYears(36)]);
    app(GestionnaireDistinctions::class)->generer(1);

    expect(DistinctionHonorifique::where('salarie_id', $this->salarie->id)->orderBy('palier_annees')->pluck('mois', 'palier_annees')->all())
        ->toEqual([10 => 1.0, 15 => 1.5, 20 => 2.0, 25 => 2.5, 30 => 3.0, 35 => 4.0]);

    ParametreAvancement::create(['entreprise_id' => 1, 'valeurs' => ['distinction_35_ans_mois' => 5.0]]);
    app()->forgetScopedInstances();
    expect(app(GestionnaireDistinctions::class)->calculer($this->salarie->fresh(), 35, today())['mois'])->toBe(5.0);
});

it('verse la gratification dans la période de paie ouverte, une seule fois', function () {
    $this->salarie->update(['date_embauche' => today()->subYears(10)->subDay()]);
    app(GestionnaireDistinctions::class)->generer(1);
    $distinction = DistinctionHonorifique::where('salarie_id', $this->salarie->id)->sole();

    $this->get(route('carriere.avancements.index'))->assertOk()->assertSee('Distinctions honorifiques dues')->assertSee('163 500 FCFA');
    $this->post(route('carriere.avancements.distinctions.verser', $distinction))->assertRedirect()->assertSessionHas('success');

    $periode = PeriodePaie::where('statut', 'open')->orderByDesc('annee')->orderByDesc('mois')->firstOrFail();
    $saisie = SaisiePaie::where('periode_id', $periode->id)->where('salarie_id', $this->salarie->id)->where('source', 'distinction')->sole();
    expect((int) $saisie->montant)->toBe(163500);
    expect($distinction->fresh()->statut)->toBe('versee');

    $this->post(route('carriere.avancements.distinctions.verser', $distinction))->assertSessionHas('error');
    expect(SaisiePaie::where('source', 'distinction')->count())->toBe(1);
});

it('lance la génération quotidienne des distinctions', function () {
    $this->salarie->update(['date_embauche' => today()->subYears(15)->subDay()]);

    $this->artisan('avancements:distinctions', ['--entreprise' => 1])->assertSuccessful();

    expect(DistinctionHonorifique::where('salarie_id', $this->salarie->id)->pluck('palier_annees')->sort()->values()->all())->toBe([10, 15]);
});
