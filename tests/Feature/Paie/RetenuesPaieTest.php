<?php

/*
| Paie — retenues encadrées : cas légaux seulement (Code art. 173, 175), pas de sanction
| pécuniaire (Code art. 120), cotisation syndicale avec accord (CCIT art. 35), prêts et
| avances remboursés par cession vérifiée, saisies-attributions et cessions plafonnées à la
| quotité saisissable (Code art. 174). Barème de quotité d'exemple : 10 % jusqu'à 30 000,
| 20 % jusqu'à 75 000, un tiers au-delà.
*/

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Carriere\Models\SituationCarriere;
use App\Domain\Paie\Models\AnomaliePaie;
use App\Domain\Paie\Models\BulletinPaie;
use App\Domain\Paie\Models\ElementPaieSalarie;
use App\Domain\Paie\Models\ParametreLegalPaie;
use App\Domain\Paie\Models\PeriodePaie;
use App\Domain\Paie\Models\PretSalarie;
use App\Domain\Paie\Models\RegleAnciennete;
use App\Domain\Paie\Models\RubriquePaie;
use App\Domain\Paie\Models\SaisieAttribution;
use App\Domain\Paie\Models\SaisiePaie;
use App\Domain\Paie\Services\CalculateurQuotite;
use App\Domain\Paie\Services\MoteurPaie;
use App\Domain\Personnel\Models\Salarie;
use Carbon\Carbon;

beforeEach(function () {
    $this->seed();
    $this->actingAs(Utilisateur::where('role', Utilisateur::ROLE_SUPER_ADMIN)->first());

    $this->salarie = Salarie::where('actif', true)->orderBy('id')->firstOrFail();
    Salarie::where('actif', true)->where('id', '!=', $this->salarie->id)->update(['actif' => false]);
    RegleAnciennete::query()->update(['actif' => false]);
    SituationCarriere::where('salarie_id', $this->salarie->id)->first()->positionClassification->update(['salaire_minimum' => 60000]);
    ElementPaieSalarie::where('salarie_id', $this->salarie->id)->delete();
    $this->rubrique = fn (string $code) => RubriquePaie::where('code', $code)->sole();
    ElementPaieSalarie::create(['entreprise_id' => 1, 'salarie_id' => $this->salarie->id, 'rubrique_id' => ($this->rubrique)('SAL-BASE')->id, 'montant' => 150000, 'actif' => true, 'etat' => 1]);
    $this->salarie->update(['date_embauche' => '2020-01-01']);

    $this->periode = PeriodePaie::updateOrCreate(['entreprise_id' => 1, 'annee' => 2026, 'mois' => 9], ['statut' => 'open', 'etat' => 1]);
    $this->quotite = fn () => ParametreLegalPaie::create(['entreprise_id' => 1, 'cle' => 'quotite_saisissable', 'debut_effet' => '2026-01-01',
        'valeur_json' => [[30000, 0.1], [75000, 0.2], [null, 1 / 3]], 'commentaire' => 'exemple']);
    $this->calculer = function (?PeriodePaie $periode = null) {
        $periode ??= $this->periode;
        app(MoteurPaie::class)->calculer($periode->fresh());

        return BulletinPaie::where('periode_id', $periode->id)->where('salarie_id', $this->salarie->id)->sole();
    };
    $this->codes = fn (BulletinPaie $b) => $b->lignes()->pluck('code')->all();
});

it('refuse une retenue hors des cas prévus par la loi, comme une sanction pécuniaire', function () {
    // L'ancienne rubrique « retenue absence » n'a pas de cas légal : la retenue n'est pas appliquée.
    SaisiePaie::create(['entreprise_id' => 1, 'periode_id' => $this->periode->id, 'salarie_id' => $this->salarie->id,
        'rubrique_id' => ($this->rubrique)('ABS-NJ')->id, 'quantite' => 1, 'taux' => 0, 'montant' => 5000, 'etat' => 1]);

    $bulletin = ($this->calculer)();

    expect(($this->codes)($bulletin))->not->toContain('ABS-NJ');
    expect(AnomaliePaie::where('code', 'retenue_non_autorisee')->value('message'))->toContain('ne correspond à aucun cas prévu par la loi');
});

it('ne prélève la cotisation syndicale qu\'avec l\'accord du salarié', function () {
    $element = ElementPaieSalarie::create(['entreprise_id' => 1, 'salarie_id' => $this->salarie->id, 'rubrique_id' => ($this->rubrique)('COT-SYND')->id, 'montant' => 2000, 'actif' => true, 'etat' => 1]);

    expect(($this->codes)(($this->calculer)()))->not->toContain('COT-SYND');
    expect(AnomaliePaie::where('code', 'cotisation_syndicale')->exists())->toBeTrue();

    $element->update(['accord_le' => '2026-08-15']);
    $bulletin = ($this->calculer)();
    expect((float) $bulletin->lignes()->where('code', 'COT-SYND')->value('montant'))->toBe(2000.0);
    expect(AnomaliePaie::where('code', 'cotisation_syndicale')->exists())->toBeFalse();
});

it('ne rembourse un prêt qu\'après vérification de la cession, échéance par échéance', function () {
    ($this->quotite)();
    $pret = PretSalarie::create(['entreprise_id' => 1, 'salarie_id' => $this->salarie->id, 'type' => 'avance', 'montant' => 25000,
        'echeance' => 10000, 'premier_mois' => '2026-09-01', 'verification' => 'inspecteur', 'statut' => 'actif']);

    expect(($this->codes)(($this->calculer)()))->not->toContain('PRET');
    expect(AnomaliePaie::where('code', 'cession')->exists())->toBeTrue();

    $pret->update(['verifie_le' => '2026-08-30']);
    expect((float) ($this->calculer)()->lignes()->where('code', 'PRET')->value('montant'))->toBe(10000.0);

    // Septembre validé : octobre retient 10 000, novembre le solde de 5 000.
    $this->periode->update(['statut' => 'validated']);
    $octobre = PeriodePaie::updateOrCreate(['entreprise_id' => 1, 'annee' => 2026, 'mois' => 10], ['statut' => 'open', 'etat' => 1]);
    expect((float) ($this->calculer)($octobre)->lignes()->where('code', 'PRET')->value('montant'))->toBe(10000.0);
    $octobre->update(['statut' => 'validated']);
    $novembre = PeriodePaie::updateOrCreate(['entreprise_id' => 1, 'annee' => 2026, 'mois' => 11], ['statut' => 'open', 'etat' => 1]);
    expect((float) ($this->calculer)($novembre)->lignes()->where('code', 'PRET')->value('montant'))->toBe(5000.0);
    expect($pret->restant($novembre->id))->toBe(5000.0);
});

it('bloque une cession tant que le barème de quotité n\'est pas paramétré', function () {
    PretSalarie::create(['entreprise_id' => 1, 'salarie_id' => $this->salarie->id, 'type' => 'pret', 'montant' => 50000,
        'echeance' => 5000, 'premier_mois' => '2026-09-01', 'verification' => 'tribunal', 'verifie_le' => '2026-08-01', 'statut' => 'actif']);

    ($this->calculer)();

    expect(AnomaliePaie::where('code', 'quotite')->where('gravite', 'bloquante')->exists())->toBeTrue();
});

it('plafonne saisie et cession à la quotité, la saisie en priorité, le reliquat restant dû', function () {
    ($this->quotite)();
    SaisieAttribution::create(['entreprise_id' => 1, 'salarie_id' => $this->salarie->id, 'creancier' => 'Banque X',
        'reference_acte' => 'SA-2026-17', 'date_acte' => '2026-08-20', 'montant_total' => 200000, 'rang' => 1, 'statut' => 'active']);
    PretSalarie::create(['entreprise_id' => 1, 'salarie_id' => $this->salarie->id, 'type' => 'avance', 'montant' => 40000,
        'echeance' => 20000, 'premier_mois' => '2026-09-01', 'verification' => 'inspecteur', 'verifie_le' => '2026-08-30', 'statut' => 'actif']);

    $bulletin = ($this->calculer)();

    $assiette = (float) $bulletin->montant_brut - (float) $bulletin->lignes()->where('nature', 'cotisation')->sum('montant') - (float) $bulletin->montant_irpp;
    $quotite = app(CalculateurQuotite::class)->quotite(1, $assiette, Carbon::parse('2026-09-30'));
    $saisie = (float) $bulletin->lignes()->where('code', 'SAISIE-ATT')->value('montant');
    $pret = (float) $bulletin->lignes()->where('code', 'PRET')->value('montant');

    expect($saisie + $pret)->toBe($quotite);
    expect($saisie)->toBe(min(200000.0, $quotite));
    expect(AnomaliePaie::where('code', 'quotite')->where('gravite', 'alerte')->value('message'))->toContain('seront retenus les mois suivants');
});

it('calcule la quotité par tranches et l\'arrondit à l\'entier inférieur', function () {
    ($this->quotite)();
    $calculateur = app(CalculateurQuotite::class);
    $date = Carbon::parse('2026-09-30');

    // 3 000 + 9 000 + (77 024 − 75 000) / 3 = 12 674,67
    expect($calculateur->quotite(1, 77024, $date))->toBe(12674.0);
    expect($calculateur->quotite(1, 20000, $date))->toBe(2000.0);
});

it('saisit le barème de quotité et les prêts depuis les écrans', function () {
    $this->post(route('paie.parametres.externes.store'), ['cle' => 'quotite_saisissable', 'tranches' => "30000 ; 10\n75000 ; 20\n ; 33,33", 'debut_effet' => '2026-01-01'])
        ->assertRedirect()->assertSessionHas('success');
    expect(ParametreLegalPaie::where('cle', 'quotite_saisissable')->sole()->valeur_json)->toBe([[30000, 0.1], [75000, 0.2], [null, 0.3333]]);
    $this->get(route('paie.parametres.index'))->assertOk()->assertSee('SMIG et quotité');

    $this->post(route('paie.elements.prets.store', $this->salarie), ['type' => 'avance', 'montant' => 30000, 'echeance' => 10000,
        'premier_mois' => '2026-10-01', 'verification' => 'inspecteur'])->assertSessionHas('success');
    $this->get(route('paie.elements.show', $this->salarie))->assertOk()->assertSee('Prêts et avances')->assertSee('Reste dû : 30 000 FCFA');
});
