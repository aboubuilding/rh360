<?php

/*
| Lot 7 — règles connexes : CDD de remplacement (RG-REM-01 à 05) et emploi inférieur (RG-INF-01 à 03).
| Grille de démonstration : catégories A (ordre 1) < B (ordre 2) < C (ordre 3).
*/

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Carriere\Enums\StatutMouvement;
use App\Domain\Carriere\Enums\TypeMouvement;
use App\Domain\Carriere\Models\MouvementCarriere;
use App\Domain\Carriere\Models\SituationCarriere;
use App\Domain\Carriere\Services\AppliqueurMouvement;
use App\Domain\Classification\Models\CategorieClassification;
use App\Domain\Classification\Models\PositionClassification;
use App\Domain\Conges\Models\Absence;
use App\Domain\Conges\Models\TypeConge;
use App\Domain\Contrats\Enums\MotifFinContrat;
use App\Domain\Contrats\Enums\MotifRecoursCdd;
use App\Domain\Contrats\Enums\TermeEvenementCdd;
use App\Domain\Contrats\Enums\TypeContrat;
use App\Domain\Contrats\Models\Contrat;
use App\Domain\Contrats\Services\IndemniteFinCddCalculator;
use App\Domain\Contrats\Services\ValidateurCddRemplacement;
use App\Domain\Interim\Enums\MotifMaintienRemuneration;
use App\Domain\Interim\Models\AlerteInterim;
use App\Domain\Interim\Models\MaintienRemuneration;
use App\Domain\Interim\Services\BalayeurInterims;
use App\Domain\Interim\Services\PreparateurPaieReglesConnexes;
use App\Domain\Organisation\Models\Poste;
use App\Domain\Paie\Models\BulletinPaie;
use App\Domain\Paie\Models\ElementPaieSalarie;
use App\Domain\Paie\Models\PeriodePaie;
use App\Domain\Paie\Models\SaisiePaie;
use App\Domain\Personnel\Models\Affectation;
use App\Domain\Personnel\Models\Salarie;
use Carbon\Carbon;

beforeEach(function () {
    $this->seed();
    $this->actingAs(Utilisateur::where('role', Utilisateur::ROLE_SUPER_ADMIN)->first());

    $this->salarie = Salarie::orderBy('id')->firstOrFail();
    $this->titulaire = Salarie::where('id', '!=', $this->salarie->id)->orderBy('id')->firstOrFail();
    $this->validateur = app(ValidateurCddRemplacement::class);
});

function cdd(array $surcharges = []): array
{
    return array_merge([
        'salarie_id' => test()->salarie->id,
        'type_contrat' => TypeContrat::CDD->value,
        'motif_recours' => MotifRecoursCdd::REMPLACEMENT_ABSENT->value,
        'titulaire_remplace_id' => test()->titulaire->id,
        'date_debut' => '2026-11-01',
        'date_fin' => '2027-04-30',
    ], $surcharges);
}

function cddSigne(string $debut, string $fin, array $surcharges = []): Contrat
{
    return Contrat::factory()->signe()->create(array_merge([
        'salarie_id' => test()->salarie->id,
        'type_contrat' => TypeContrat::CDD->value,
        'date_debut' => $debut,
        'date_fin' => $fin,
    ], $surcharges));
}

function salaireDeBase(): int
{
    return (int) ElementPaieSalarie::where('salarie_id', test()->salarie->id)
        ->whereHas('rubrique', fn ($q) => $q->where('code', 'SAL-BASE'))->value('montant');
}

function baisserSalaireDeBase(int $montant): void
{
    ElementPaieSalarie::where('salarie_id', test()->salarie->id)
        ->whereHas('rubrique', fn ($q) => $q->where('code', 'SAL-BASE'))->update(['montant' => $montant]);
}

function mouvementApplique(array $attributs): MouvementCarriere
{
    $mouvement = MouvementCarriere::factory()->create(array_merge([
        'entreprise_id' => 1,
        'salarie_id' => test()->salarie->id,
        'type_mouvement' => TypeMouvement::AFFECTATION->value,
        'statut' => StatutMouvement::PROGRAMME->value,
        'date_effet' => today()->subDay(),
    ], $attributs));

    return app(AppliqueurMouvement::class)->appliquer($mouvement);
}

// --- RG-REM-01 ---

it('RG-REM-01 : exige le salarié remplacé sur un CDD de remplacement', function () {
    expect($this->validateur->controler(cdd(['titulaire_remplace_id' => null])))
        ->toHaveKey('titulaire_remplace_id');
    expect($this->validateur->controler(cdd(['titulaire_remplace_id' => $this->salarie->id]))['titulaire_remplace_id'][0])
        ->toContain('ne peut pas être le titulaire');
    expect($this->validateur->controler(cdd()))->toBe([]);
    expect($this->validateur->controler(cdd(['motif_recours' => MotifRecoursCdd::ATTENTE_CDI->value, 'titulaire_remplace_id' => null])))->toBe([]);
});

// --- RG-REM-02 ---

it('RG-REM-02 : admet un terme fixé par le retour du titulaire, à défaut de date', function () {
    expect($this->validateur->controler(cdd(['date_fin' => null, 'terme_evenement' => TermeEvenementCdd::RETOUR_TITULAIRE->value])))->toBe([]);
    expect($this->validateur->controler(cdd(['date_fin' => null]))['date_fin'][0])->toContain('Un CDD a un terme');
    expect($this->validateur->controler(cdd([
        'motif_recours' => MotifRecoursCdd::AUTRE->value, 'titulaire_remplace_id' => null,
        'date_fin' => null, 'terme_evenement' => TermeEvenementCdd::RETOUR_TITULAIRE->value,
    ])))->toHaveKey('terme_evenement');
});

it('RG-REM-02 : alerte quand le titulaire n\'est plus absent', function () {
    $contrat = cddSigne('2026-09-01', '2027-08-31', [
        'motif_recours' => MotifRecoursCdd::REMPLACEMENT_ABSENT->value, 'titulaire_remplace_id' => $this->titulaire->id,
        'terme_evenement' => TermeEvenementCdd::RETOUR_TITULAIRE->value,
    ]);
    Absence::create(['entreprise_id' => 1, 'salarie_id' => $this->titulaire->id,
        'type_conge_id' => TypeConge::where('code', 'MAL')->value('id'), 'debut_le' => '2026-08-25 00:00:00', 'fin_le' => '2026-11-15 00:00:00']);
    $alerte = fn () => AlerteInterim::where('objet_type', $contrat->getMorphClass())->where('objet_id', $contrat->id)->value('en_cours');

    $this->travelTo('2026-11-10 06:15:00');
    app(BalayeurInterims::class)->balayer(1);
    expect($alerte())->toBeNull();

    $this->travelTo('2026-11-16 06:15:00');
    app(BalayeurInterims::class)->balayer(1);
    expect((bool) $alerte())->toBeTrue();
});

// --- RG-REM-03 ---

it('RG-REM-03 : limite la durée initiale à 2 ans et la durée totale à 4 ans', function () {
    expect($this->validateur->controler(cdd(['date_fin' => '2028-10-31'])))->toBe([]);
    expect($this->validateur->controler(cdd(['date_fin' => '2028-11-01']))['date_fin'][0])
        ->toContain('durée initiale d\'un CDD est limitée à 2 ans')->toContain('31/10/2028')->not->toContain('art.');

    $initial = cddSigne('2026-11-01', '2028-10-31');
    expect($this->validateur->controler(cdd(['date_debut' => '2028-11-01', 'date_fin' => '2030-10-31']), $initial))->toBe([]);
    expect($this->validateur->controler(cdd(['date_debut' => '2028-11-01', 'date_fin' => '2030-11-01']), $initial)['date_fin'][0])
        ->toContain('renouvellements compris, est limitée à 4 ans');
});

it('RG-REM-03 : enregistre un CDD écrit, ou un journalier payé en fin de journée', function () {
    $poste = Poste::first();
    $position = PositionClassification::first();

    $this->postJson(route('contrats.contrats.store'), cdd([
        'reference' => 'CTR-REM-001', 'poste_id' => $poste->id, 'position_classification_id' => $position->id,
        'journalier_paye_fin_journee' => '1',
    ]))->assertOk();

    $contrat = Contrat::where('reference', 'CTR-REM-001')->sole();
    expect($contrat->journalier_paye_fin_journee)->toBeTrue();
    expect($contrat->motif_recours)->toBe(MotifRecoursCdd::REMPLACEMENT_ABSENT);
    expect($contrat->titulaire_remplace_id)->toBe($this->titulaire->id);
    $this->get(route('contrats.contrats.show', $contrat))->assertOk()->assertSee('CDD — motif de recours et terme');
});

// --- RG-REM-04 ---

it('RG-REM-04 : ouvre droit à l\'indemnité après 4 ans de CDD consécutifs sans CDI', function () {
    cddSigne('2022-11-01', '2024-10-31');
    $dernier = cddSigne('2024-11-01', '2026-10-31');
    $calculateur = app(IndemniteFinCddCalculator::class);

    expect($calculateur->chaine($dernier))->toHaveCount(2);
    expect($calculateur->evaluer($dernier)['due'])->toBeTrue();

    $court = cddSigne('2025-01-01', '2026-06-30', ['salarie_id' => $this->titulaire->id]);
    expect($calculateur->evaluer($court)['due'])->toBeFalse();
});

it('RG-REM-04 : exclut l\'indemnité après embauche en CDI, refus d\'un CDI équivalent ou fin pour motif économique', function (string $cas) {
    cddSigne('2022-11-01', '2024-10-31');
    $dernier = cddSigne('2024-11-01', '2026-10-31');
    match ($cas) {
        'cdi' => Contrat::factory()->signe()->create(['salarie_id' => $this->salarie->id, 'type_contrat' => TypeContrat::CDI->value, 'date_debut' => '2026-11-01']),
        'refus' => $dernier->update(['refus_cdi_equivalent_le' => '2026-10-15']),
        'economique' => $dernier->update(['motif_fin' => MotifFinContrat::MOTIF_ECONOMIQUE->value]),
    };

    expect(app(IndemniteFinCddCalculator::class)->evaluer($dernier->fresh())['due'])->toBeFalse();
})->with(['cdi', 'refus', 'economique']);

it('RG-REM-04 : verse un mois du dernier salaire brut avec le dernier salaire', function () {
    $periode = PeriodePaie::where('statut', 'open')->firstOrFail();
    $finMois = Carbon::create($periode->annee, $periode->mois, 1)->endOfMonth();
    cddSigne($finMois->copy()->subYears(4)->addDay()->toDateString(), $finMois->copy()->subYears(2)->toDateString());
    cddSigne($finMois->copy()->subYears(2)->addDay()->toDateString(), $finMois->toDateString());
    $precedente = PeriodePaie::where('statut', 'validated')->firstOrFail();
    BulletinPaie::create(['entreprise_id' => 1, 'periode_id' => $precedente->id, 'salarie_id' => $this->salarie->id,
        'montant_brut' => 312500, 'calcule_le' => now()]);

    $rapport = app(PreparateurPaieReglesConnexes::class)->preparer($periode);

    $saisie = SaisiePaie::where('periode_id', $periode->id)->where('source', 'fin_cdd')->sole();
    expect((int) $saisie->montant)->toBe(312500);
    expect($saisie->observations)->not->toContain('art.');
    expect($rapport->anomalies)->toBe([]);
});

it('RG-REM-04 : signale l\'indemnité due sans bulletin antérieur', function () {
    $periode = PeriodePaie::where('statut', 'open')->firstOrFail();
    $finMois = Carbon::create($periode->annee, $periode->mois, 1)->endOfMonth();
    cddSigne($finMois->copy()->subYears(4)->addDay()->toDateString(), $finMois->toDateString(), ['date_fin' => $finMois->toDateString()]);

    $rapport = app(PreparateurPaieReglesConnexes::class)->preparer($periode);

    expect(SaisiePaie::where('periode_id', $periode->id)->where('source', 'fin_cdd')->count())->toBe(0);
    expect($rapport->anomalies[0]['message'])->toContain('dernier salaire brut');
});

it('RG-REM-04 : enregistre le motif de fin et le refus d\'un CDI depuis la fiche du contrat', function () {
    $contrat = cddSigne('2025-01-01', '2026-12-31');

    $this->post(route('contrats.contrats.fin-cdd', $contrat), [
        'motif_fin' => MotifFinContrat::MOTIF_ECONOMIQUE->value, 'refus_cdi_equivalent_le' => '2026-12-01',
    ])->assertRedirect();

    expect($contrat->fresh()->motif_fin)->toBe(MotifFinContrat::MOTIF_ECONOMIQUE);
    expect($contrat->fresh()->refus_cdi_equivalent_le->toDateString())->toBe('2026-12-01');
});

// --- RG-REM-05 ---

it('RG-REM-05 : exige la mention du caractère provisoire pour remplacer un salarié malade', function () {
    Absence::create(['entreprise_id' => 1, 'salarie_id' => $this->titulaire->id,
        'type_conge_id' => TypeConge::where('code', 'MAL')->value('id'), 'debut_le' => '2026-10-01 00:00:00']);

    expect($this->validateur->controler(cdd())['mention_caractere_provisoire'][0])
        ->toContain('caractère provisoire')->not->toContain('art.');
    expect($this->validateur->controler(cdd(['mention_caractere_provisoire' => 'Emploi provisoire : remplacement de M. X, absent pour maladie.'])))->toBe([]);
});

// --- RG-INF-01 ---

it('RG-INF-01 : maintient salaire et classement pendant une affectation provisoire à un emploi inférieur', function () {
    $courante = Affectation::where('salarie_id', $this->salarie->id)->where('en_cours', true)->sole();
    Poste::findOrFail($courante->poste_id)->update(['categorie_classification_id' => CategorieClassification::where('code', 'B')->value('id')]);
    $inferieur = Poste::where('id', '!=', $courante->poste_id)->firstOrFail();
    $inferieur->update(['categorie_classification_id' => CategorieClassification::where('code', 'A')->value('id')]);
    $position = SituationCarriere::where('salarie_id', $this->salarie->id)->value('position_classification_id');
    $salaire = salaireDeBase();

    mouvementApplique(['poste_cible_id' => $inferieur->id, 'structure_cible_id' => $inferieur->structure_id, 'date_fin_prevue' => today()->addMonths(2)]);

    $maintien = MaintienRemuneration::sole();
    expect($maintien->motif)->toBe(MotifMaintienRemuneration::AFFECTATION_PROVISOIRE_INFERIEURE);
    expect($maintien->salaire_base_maintenu)->toBe($salaire);
    expect(salaireDeBase())->toBe($salaire);
    expect(SituationCarriere::where('salarie_id', $this->salarie->id)->value('position_classification_id'))->toBe($position);

    // Une baisse du salaire de base est compensée par la paie.
    baisserSalaireDeBase($salaire - 20000);
    app(PreparateurPaieReglesConnexes::class)->preparer(PeriodePaie::where('statut', 'open')->firstOrFail());
    expect(SaisiePaie::where('source', 'maintien_salaire')->where('salarie_id', $this->salarie->id)->sum('montant'))->toBeGreaterThan(0);
});

// --- RG-INF-02 ---

it('RG-INF-02 : exige la consultation des délégués pour une affectation définitive à un poste inférieur', function () {
    SituationCarriere::where('salarie_id', $this->salarie->id)
        ->update(['position_classification_id' => PositionClassification::where('code', 'B1-A')->value('id')]);
    $courante = Affectation::where('salarie_id', $this->salarie->id)->where('en_cours', true)->sole();
    Poste::findOrFail($courante->poste_id)->update(['categorie_classification_id' => CategorieClassification::where('code', 'B')->value('id')]);
    $inferieur = Poste::where('id', '!=', $courante->poste_id)->firstOrFail();
    $inferieur->update(['categorie_classification_id' => CategorieClassification::where('code', 'A')->value('id')]);
    $cible = ['poste_cible_id' => $inferieur->id, 'structure_cible_id' => $inferieur->structure_id];

    expect(fn () => mouvementApplique($cible))->toThrow(DomainException::class, 'consultation préalable des délégués du personnel');
    expect(Affectation::where('salarie_id', $this->salarie->id)->where('en_cours', true)->sole()->id)->toBe($courante->id);

    mouvementApplique($cible + ['date_consultation_delegues' => today()->subWeek(), 'chemin_pv_consultation' => 'carriere/pv-consultation/pv.pdf']);
    $maintien = MaintienRemuneration::sole();
    expect($maintien->motif)->toBe(MotifMaintienRemuneration::AFFECTATION_DEFINITIVE_INFERIEURE);
    expect($maintien->date_fin)->toBeNull();

    // Classement maintenu : une rétrogradation de catégorie est refusée.
    expect(fn () => mouvementApplique([
        'type_mouvement' => TypeMouvement::RECLASSEMENT->value,
        'position_classification_cible_id' => PositionClassification::where('code', 'A1-A')->value('id'),
    ]))->toThrow(DomainException::class, 'le classement est maintenu');
});

// --- RG-INF-03 ---

it('RG-INF-03 : maintient le salaire antérieur d\'une salariée enceinte mutée en raison de son état', function () {
    $autre = Poste::where('id', '!=', Affectation::where('salarie_id', $this->salarie->id)->where('en_cours', true)->value('poste_id'))->firstOrFail();
    $salaire = salaireDeBase();

    mouvementApplique([
        'type_mouvement' => TypeMouvement::MUTATION->value, 'sous_type_mouvement' => 'mutation_etat_grossesse',
        'poste_cible_id' => $autre->id, 'structure_cible_id' => $autre->structure_id,
    ]);

    $maintien = MaintienRemuneration::sole();
    expect($maintien->motif)->toBe(MotifMaintienRemuneration::MUTATION_ETAT_GROSSESSE);
    expect($maintien->salaire_base_maintenu)->toBe($salaire);

    baisserSalaireDeBase($salaire - 50000);
    $periode = PeriodePaie::where('statut', 'open')->firstOrFail();
    app(PreparateurPaieReglesConnexes::class)->preparer($periode);
    $debutMois = Carbon::create($periode->annee, $periode->mois, 1);
    $jours = $debutMois->daysInMonth - $maintien->date_debut->max($debutMois)->day + 1;
    expect((int) SaisiePaie::where('source', 'maintien_salaire')->where('salarie_id', $this->salarie->id)->value('montant'))
        ->toBe((int) round(50000 * $jours / $debutMois->daysInMonth));
});
