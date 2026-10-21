<?php

/*
| Paie — clôture : date de paiement au plus tard 8 jours après la fin du mois, jamais un
| dimanche ni un jour férié (Code art. 163, 164) ; recalculs journalisés.
*/

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Carriere\Models\SituationCarriere;
use App\Domain\Conges\Models\JourFerie;
use App\Domain\Paie\Actions\ValiderPeriode;
use App\Domain\Paie\Models\ElementPaieSalarie;
use App\Domain\Paie\Models\PeriodePaie;
use App\Domain\Paie\Models\RegleAnciennete;
use App\Domain\Paie\Models\RubriquePaie;
use App\Domain\Paie\Services\MoteurPaie;
use App\Domain\Personnel\Models\Salarie;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed();
    $this->actingAs(Utilisateur::where('role', Utilisateur::ROLE_SUPER_ADMIN)->first());
    $this->salarie = Salarie::where('actif', true)->orderBy('id')->firstOrFail();
    Salarie::where('actif', true)->where('id', '!=', $this->salarie->id)->update(['actif' => false]);
    RegleAnciennete::query()->update(['actif' => false]);
    SituationCarriere::where('salarie_id', $this->salarie->id)->first()->positionClassification->update(['salaire_minimum' => 60000]);
    ElementPaieSalarie::where('salarie_id', $this->salarie->id)->delete();
    $this->salaire = ElementPaieSalarie::create(['entreprise_id' => 1, 'salarie_id' => $this->salarie->id, 'rubrique_id' => RubriquePaie::where('code', 'SAL-BASE')->value('id'), 'montant' => 90000, 'actif' => true, 'etat' => 1]);
    // Septembre 2026 : fin le mercredi 30, limite de paiement le jeudi 8 octobre.
    $this->periode = PeriodePaie::updateOrCreate(['entreprise_id' => 1, 'annee' => 2026, 'mois' => 9], ['statut' => 'open', 'etat' => 1]);
    app(MoteurPaie::class)->calculer($this->periode->fresh());
});

it('refuse un paiement plus de 8 jours après la fin du mois ou un jour de repos', function () {
    $valider = fn (string $date) => app(ValiderPeriode::class)->executer($this->periode->fresh(), $date);

    expect(fn () => $valider('2026-10-09'))->toThrow(DomainException::class, 'au plus tard 8 jours');
    expect(fn () => $valider('2026-10-04'))->toThrow(DomainException::class, 'jour de repos');
    JourFerie::create(['entreprise_id' => 1, 'date' => '2026-10-05', 'libelle' => 'Jour décrété']);
    expect(fn () => $valider('2026-10-05'))->toThrow(DomainException::class, 'jour de repos');

    expect($valider('2026-10-08')->date_paiement->toDateString())->toBe('2026-10-08');
});

it('valide depuis l\'écran avec la date de paiement, qui figure sur le bulletin', function () {
    $this->postJson(route('paie.periodes.valider', $this->periode), ['confirmation' => 1])->assertStatus(422)->assertJsonValidationErrors(['date_paiement']);
    $this->postJson(route('paie.periodes.valider', $this->periode), ['confirmation' => 1, 'date_paiement' => '2026-09-30'])->assertOk();

    $bulletin = $this->periode->bulletins()->first();
    $this->get(route('paie.modeles.apercu.document', [\App\Domain\Paie\Models\ModelePaie::first(), 'salarie_id' => $this->salarie->id]))->assertOk();
    expect(view('paie.bulletins.pdf', ['bulletin' => $bulletin->load('salarie', 'periode', 'lignes.rubrique'), 'entreprise' => null])->render())
        ->toContain('payé le 30/09/2026');
});

it('journalise un recalcul qui change le bulletin', function () {
    $this->salaire->update(['montant' => 95000]);
    app(MoteurPaie::class)->calculer($this->periode->fresh());

    $journal = DB::table('journal_recalculs_paie')->where('salarie_id', $this->salarie->id)->sole();
    expect([(float) $journal->ancien_brut, (float) $journal->nouveau_brut])->toBe([90000.0, 95000.0]);

    // Un recalcul sans changement n'ajoute rien.
    app(MoteurPaie::class)->calculer($this->periode->fresh());
    expect(DB::table('journal_recalculs_paie')->count())->toBe(1);
});
