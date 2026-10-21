<?php

/*
| Paie — corrections du moteur : heures supplémentaires et rappels propres à chaque salarié,
| prorata de la présence à l'entrée, remboursements de frais hors brut (Code art. 162),
| cotisations patronales affichées sans être déduites (CCIT art. 35), minimum de la catégorie
| et SMIG (CCIT art. 28), catégorie et emploi sur le bulletin, anomalies bloquant la validation.
*/

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Carriere\Models\MouvementCarriere;
use App\Domain\Carriere\Models\SituationCarriere;
use App\Domain\Paie\Actions\ValiderPeriode;
use App\Domain\Paie\Models\AnomaliePaie;
use App\Domain\Paie\Models\BulletinPaie;
use App\Domain\Paie\Models\ElementPaieSalarie;
use App\Domain\Paie\Models\HeureSupplementaire;
use App\Domain\Paie\Models\ParametreLegalPaie;
use App\Domain\Paie\Models\PeriodePaie;
use App\Domain\Paie\Models\RappelAvancement;
use App\Domain\Paie\Models\RegleAnciennete;
use App\Domain\Paie\Models\RubriquePaie;
use App\Domain\Paie\Services\MoteurPaie;
use App\Domain\Personnel\Models\Salarie;

beforeEach(function () {
    $this->seed();
    $this->actingAs(Utilisateur::where('role', Utilisateur::ROLE_SUPER_ADMIN)->first());

    // Deux salariés actifs seulement, pour des bulletins et des anomalies maîtrisés.
    [$this->a, $this->b] = Salarie::where('actif', true)->orderBy('id')->take(2)->get()->all();
    Salarie::where('actif', true)->whereNotIn('id', [$this->a->id, $this->b->id])->update(['actif' => false]);
    RegleAnciennete::query()->update(['actif' => false]);

    $this->periode = PeriodePaie::updateOrCreate(['entreprise_id' => 1, 'annee' => 2026, 'mois' => 9], ['statut' => 'open', 'etat' => 1]);
    $this->rubrique = fn (string $code) => RubriquePaie::where('code', $code)->sole();
    $this->element = fn (Salarie $s, string $code, float $montant) => ElementPaieSalarie::updateOrCreate(
        ['salarie_id' => $s->id, 'rubrique_id' => ($this->rubrique)($code)->id],
        ['entreprise_id' => 1, 'montant' => $montant, 'actif' => true, 'etat' => 1],
    );
    // Salaire de base égal au minimum de la position de chaque salarié.
    foreach ([$this->a, $this->b] as $s) {
        SituationCarriere::where('salarie_id', $s->id)->first()->positionClassification->update(['salaire_minimum' => 75500]);
        ElementPaieSalarie::where('salarie_id', $s->id)->delete();
        ($this->element)($s, 'SAL-BASE', 75500);
        $s->update(['date_embauche' => '2020-01-01']);
    }

    $this->calculer = fn () => app(MoteurPaie::class)->calculer($this->periode->fresh());
    $this->bulletin = fn (Salarie $s) => BulletinPaie::where('periode_id', $this->periode->id)->where('salarie_id', $s->id)->sole();
    $this->ligne = fn (Salarie $s, string $code) => ($this->bulletin)($s)->lignes()->where('code', $code)->first();
});

it('ne verse à chaque salarié que ses propres heures supplémentaires et rappels', function () {
    HeureSupplementaire::factory()->create(['salarie_id' => $this->a->id, 'periode_paiement_id' => $this->periode->id, 'heures_hs20' => 5, 'taux_horaire' => 435.585]);
    RappelAvancement::create([
        'entreprise_id' => 1, 'salarie_id' => $this->a->id, 'mouvement_id' => MouvementCarriere::factory()->create(['salarie_id' => $this->a->id])->id,
        'bulletin_source_id' => BulletinPaie::factory()->create(['salarie_id' => $this->a->id])->id,
        'periode_generation_id' => $this->periode->id, 'montant_rappel_base' => 12000, 'montant_rappel_anciennete' => 0,
    ]);

    ($this->calculer)();

    // 5 h × 435,585 × 1,20 = 2 613,51
    expect((float) ($this->ligne)($this->a, 'HS')->montant)->toBe(2613.51);
    expect((float) ($this->ligne)($this->a, 'RAPPEL')->montant)->toBe(12000.0);
    expect(($this->ligne)($this->b, 'HS'))->toBeNull();
    expect(($this->ligne)($this->b, 'RAPPEL'))->toBeNull();
});

it('proratise le salaire et les éléments fixes à l\'entrée en cours de mois', function () {
    // Entrée le 10 septembre : 21 jours sur 30.
    $this->a->update(['date_embauche' => '2026-09-10']);
    ($this->element)($this->a, 'PRIME-LOGT', 30000);

    ($this->calculer)();

    $bulletin = ($this->bulletin)($this->a);
    expect([$bulletin->jours_presence, $bulletin->jours_mois])->toBe([21, 30]);
    expect((float) ($this->ligne)($this->a, 'SAL-BASE')->montant)->toBe(52850.0);
    expect((float) ($this->ligne)($this->a, 'PRIME-LOGT')->montant)->toBe(21000.0);
    expect(($this->ligne)($this->a, 'SAL-BASE')->libelle)->toBe('Salaire de base (21/30 jours)');
});

it('sort les remboursements de frais du brut et des cotisations, et les ajoute au net', function () {
    ($this->element)($this->a, 'PRIME-TRANSP', 10000);

    ($this->calculer)();

    $bulletin = ($this->bulletin)($this->a);
    expect((float) $bulletin->montant_brut)->toBe(75500.0);
    expect((float) $bulletin->assiette_cotisations)->toBe(75500.0);
    expect((float) $bulletin->montant_frais)->toBe(10000.0);
    // Net = brut − cotisations salariales − impôt + frais.
    expect((float) $bulletin->montant_net)->toBe(round(75500 - (float) $bulletin->montant_retenues + 10000, 2));
});

it('affiche les cotisations patronales sans les déduire du salaire', function () {
    ($this->calculer)();

    $bulletin = ($this->bulletin)($this->a);
    // CNSS 15 % et AMU 5 % de 75 500 (taux du jeu de démonstration).
    expect((float) ($this->ligne)($this->a, 'CNSS-PAT')->montant)->toBe(11325.0);
    expect((float) ($this->ligne)($this->a, 'AMU-PAT')->montant)->toBe(3775.0);
    expect((float) $bulletin->montant_cotisations_patronales)->toBe(15100.0);
    // Seules les parts salariales (4 % et 5 %) et l'impôt sont retenus.
    $salariales = (float) $bulletin->lignes()->where('nature', 'cotisation')->sum('montant');
    expect($salariales)->toEqual(6795);
    expect((float) $bulletin->montant_retenues)->toBe(round($salariales + (float) $bulletin->montant_irpp, 2));

    $this->get(route('paie.bulletins.show', $bulletin))->assertOk()
        ->assertSee('Cotisations patronales')->assertSee('non déduites du salaire');
});

it('bloque la validation d\'un salaire sous le minimum de la catégorie ou le SMIG', function () {
    ($this->element)($this->a, 'SAL-BASE', 70000);
    // Salarié B sans minimum de barème, payé sous le SMIG.
    SituationCarriere::where('salarie_id', $this->b->id)->first()->positionClassification->update(['salaire_minimum' => null]);
    ($this->element)($this->b, 'SAL-BASE', 50000);

    $resultats = ($this->calculer)();

    expect($resultats['anomalies_bloquantes'])->toBe(2);
    expect(AnomaliePaie::where('salarie_id', $this->a->id)->value('code'))->toBe('minimum_categorie');
    expect(AnomaliePaie::where('salarie_id', $this->b->id)->value('message'))->toBe('Salaire de base de 50 000 FCFA inférieur au SMIG (52 500 FCFA).');
    expect(fn () => app(ValiderPeriode::class)->executer($this->periode->fresh()))
        ->toThrow(DomainException::class, '2 anomalie(s) bloquante(s)');

    $this->get(route('paie.periodes.show', $this->periode))->assertOk()
        ->assertSee('Anomalies du calcul')->assertSee('la période ne peut pas être validée');

    // Après correction et recalcul, les anomalies disparaissent.
    ($this->element)($this->a, 'SAL-BASE', 75500);
    ($this->element)($this->b, 'SAL-BASE', 52500);
    expect(($this->calculer)()['anomalies_bloquantes'])->toBe(0);
    expect(app(ValiderPeriode::class)->executer($this->periode->fresh())->statut->value)->toBe('validated');
});

it('suit le SMIG en vigueur à la date de la période', function () {
    SituationCarriere::where('salarie_id', $this->a->id)->first()->positionClassification->update(['salaire_minimum' => null]);
    ($this->element)($this->a, 'SAL-BASE', 55000);
    ParametreLegalPaie::create(['entreprise_id' => 1, 'cle' => 'smig', 'valeur' => 60000, 'debut_effet' => '2026-10-01']);

    ($this->calculer)();
    expect(AnomaliePaie::where('salarie_id', $this->a->id)->where('code', 'smig')->exists())->toBeFalse();

    $octobre = PeriodePaie::updateOrCreate(['entreprise_id' => 1, 'annee' => 2026, 'mois' => 10], ['statut' => 'open', 'etat' => 1]);
    app(MoteurPaie::class)->calculer($octobre);
    expect(AnomaliePaie::where('periode_id', $octobre->id)->where('salarie_id', $this->a->id)->where('code', 'smig')->exists())->toBeTrue();
});

it('porte la catégorie, l\'échelon et l\'emploi sur le bulletin', function () {
    ($this->calculer)();

    $bulletin = ($this->bulletin)($this->a);
    expect($bulletin->categorie_libelle)->not->toBeNull();
    expect($bulletin->emploi_libelle)->toBe($this->a->fresh()->affectationCourante->poste->intitule);
    $this->get(route('paie.bulletins.show', $bulletin))->assertOk()->assertSee('Catégorie')->assertSee($bulletin->emploi_libelle);
});

it('signale un salarié sans catégorie au lieu de l\'ignorer', function () {
    SituationCarriere::where('salarie_id', $this->b->id)->update(['position_classification_id' => null]);

    ($this->calculer)();

    expect(AnomaliePaie::where('salarie_id', $this->b->id)->pluck('code')->all())->toContain('categorie');
});
