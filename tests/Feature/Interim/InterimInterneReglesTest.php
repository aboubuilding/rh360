<?php

/*
| Lot 2 — règles du module A (intérim interne d'un emploi supérieur, CCIT art. 13).
| Grille de démonstration : catégories A (ordre 1) < B (ordre 2) < C (ordre 3).
*/

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Carriere\Enums\StatutMouvement;
use App\Domain\Carriere\Enums\TypeMouvement;
use App\Domain\Carriere\Models\MouvementCarriere;
use App\Domain\Carriere\Models\SituationCarriere;
use App\Domain\Classification\Models\CategorieClassification;
use App\Domain\Classification\Models\PositionClassification;
use App\Domain\Conges\Models\Absence;
use App\Domain\Conges\Models\TypeConge;
use App\Domain\Interim\Enums\CauseFinInterim;
use App\Domain\Interim\Enums\DecisionFinInterim;
use App\Domain\Interim\Enums\MotifInterim;
use App\Domain\Interim\Enums\StatutInterim;
use App\Domain\Interim\Enums\TypeDecisionInterim;
use App\Domain\Interim\Models\InterimInterne;
use App\Domain\Interim\Services\InterimInterneService;
use App\Domain\Organisation\Models\Poste;
use App\Domain\Paie\Models\ElementPaieSalarie;
use App\Domain\Paie\Models\RubriquePaie;
use App\Domain\Personnel\Models\Affectation;
use App\Domain\Personnel\Models\Salarie;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed();
    $this->actingAs(Utilisateur::where('role', Utilisateur::ROLE_SUPER_ADMIN)->first());
    Storage::fake('local');

    $this->categorieA = CategorieClassification::where('code', 'A')->firstOrFail();
    $this->categorieB = CategorieClassification::where('code', 'B')->firstOrFail();

    // Salarié en catégorie A (position A1-A), titulaire distinct.
    $this->salarie = Salarie::orderBy('id')->firstOrFail();
    $this->titulaire = Salarie::where('id', '!=', $this->salarie->id)->orderBy('id')->firstOrFail();
    SituationCarriere::where('salarie_id', $this->salarie->id)
        ->update(['position_classification_id' => PositionClassification::where('code', 'A1-A')->value('id')]);

    $this->posteSuperieur = Poste::where('intitule', 'Gestionnaire RH')->firstOrFail();
    $this->posteSuperieur->update(['categorie_classification_id' => $this->categorieB->id]);
    $this->posteEgal = Poste::where('intitule', 'Secrétaire')->firstOrFail();
    $this->posteEgal->update(['categorie_classification_id' => $this->categorieA->id]);

    $this->service = app(InterimInterneService::class);
});

function donneesInterim(array $surcharges = []): array
{
    return array_merge([
        'salarie_id' => test()->salarie->id,
        'poste_occupe_id' => test()->posteSuperieur->id,
        'titulaire_id' => test()->titulaire->id,
        'motif' => MotifInterim::POSTE_VACANT->value,
        'date_debut' => '2026-09-19',
        'date_fin_prevue' => '2026-11-18',
    ], $surcharges);
}

function piece(): UploadedFile
{
    return UploadedFile::fake()->create('piece.pdf', 20, 'application/pdf');
}

function erreursDe(callable $action): array
{
    try {
        $action();
    } catch (ValidationException $e) {
        return $e->errors();
    }

    throw new RuntimeException('Une ValidationException était attendue.');
}

function absenceTitulaire(string $code, string $debut, ?string $fin = null): Absence
{
    return Absence::create([
        'entreprise_id' => 1,
        'salarie_id' => test()->titulaire->id,
        'type_conge_id' => TypeConge::where('code', $code)->value('id'),
        'debut_le' => $debut,
        'fin_le' => $fin,
    ]);
}

// --- RG-INT-01 ---

it('RG-INT-01 : crée un intérim sur un poste de catégorie strictement supérieure', function () {
    $interim = $this->service->creer(donneesInterim());

    expect($interim->categorie_occupee_id)->toBe($this->categorieB->id);
    expect($interim->categorie_origine_id)->toBe($this->categorieA->id);
    expect($interim->titulaire_id)->toBe($this->titulaire->id);
    expect($interim->statut)->toBe(StatutInterim::EN_COURS);

    // Le mouvement de carrière reste l'acte de l'historique.
    $mouvement = $interim->mouvementCarriere;
    expect($mouvement->type_mouvement)->toBe(TypeMouvement::INTERIM);
    expect($mouvement->poste_cible_id)->toBe($this->posteSuperieur->id);
    expect($interim->decisions()->first()->type_decision)->toBe(TypeDecisionInterim::CREATION);
});

it('RG-INT-01 : refuse un poste de catégorie égale', function () {
    $erreurs = erreursDe(fn () => $this->service->creer(donneesInterim(['poste_occupe_id' => $this->posteEgal->id])));

    expect($erreurs['poste_occupe_id'][0])
        ->toContain('strictement supérieure')
        ->not->toContain('art.');
    expect(InterimInterne::count())->toBe(0);
});

it('RG-INT-01 : refuse un poste de catégorie inférieure', function () {
    SituationCarriere::where('salarie_id', $this->salarie->id)
        ->update(['position_classification_id' => PositionClassification::where('code', 'B1-A')->value('id')]);

    $erreurs = erreursDe(fn () => $this->service->creer(donneesInterim(['poste_occupe_id' => $this->posteEgal->id])));

    expect($erreurs['poste_occupe_id'][0])->toContain('strictement supérieure');
});

it('RG-INT-01 : refuse un poste non rattaché à une catégorie de classification', function () {
    $erreurs = erreursDe(fn () => $this->service->creer(donneesInterim(['poste_occupe_id' => Poste::where('intitule', 'Directeur général')->firstOrFail()->id])));

    expect($erreurs['poste_occupe_id'][0])->toContain('aucune catégorie de classification');
});

// --- RG-INT-02 ---

it('RG-INT-02 : conserve le poste permanent, la position et le salaire de base du salarié', function () {
    $affectation = Affectation::where('salarie_id', $this->salarie->id)->where('en_cours', true)->sole();
    $position = SituationCarriere::where('salarie_id', $this->salarie->id)->value('position_classification_id');
    $salaire = ElementPaieSalarie::where('salarie_id', $this->salarie->id)
        ->whereHas('rubrique', fn ($q) => $q->where('code', 'SAL-BASE'))->value('montant');

    $interim = $this->service->creer(donneesInterim());

    expect(Affectation::where('salarie_id', $this->salarie->id)->where('en_cours', true)->sole()->id)->toBe($affectation->id);
    expect(SituationCarriere::where('salarie_id', $this->salarie->id)->value('position_classification_id'))->toBe($position);
    expect($interim->salaire_base_origine)->toBe($salaire === null ? null : (int) $salaire);
    expect($interim->affectation_origine_id)->toBe($affectation->id);
});

// --- RG-INT-03 ---

it('RG-INT-03 : accepte une durée de 2 mois date à date (du 19/09 au 18/11 inclus)', function () {
    $interim = $this->service->creer(donneesInterim());

    expect($interim->date_limite->toDateString())->toBe('2026-11-18');
});

it('RG-INT-03 : refuse une fin au-delà de 2 mois sans motif éligible', function () {
    $erreurs = erreursDe(fn () => $this->service->creer(donneesInterim(['date_fin_prevue' => '2026-11-19'])));

    expect($erreurs['date_fin_prevue'][0])
        ->toContain('au plus tard le 18/11/2026')
        ->toContain('ne permet pas de prolongation')
        ->not->toContain('art.');
});

it('RG-INT-03 : cumule les intérims successifs sur le même poste et le même titulaire', function () {
    $this->service->creer(donneesInterim(['date_debut' => '2026-08-01', 'date_fin_prevue' => '2026-09-01']));
    $premier = InterimInterne::sole();
    $this->service->cloturer($premier, CauseFinInterim::RETOUR_TITULAIRE, '2026-09-01');

    // 32 jours déjà effectués (01/08 au 01/09 inclus) : la nouvelle limite est le 01/11 - 32 j.
    $erreurs = erreursDe(fn () => $this->service->creer(donneesInterim([
        'date_debut' => '2026-09-02', 'date_fin_prevue' => '2026-11-02',
    ])));

    expect($erreurs['date_fin_prevue'][0])->toContain('32 jours déjà effectués');
});

it('RG-INT-03 : ne cumule pas un intérim séparé par une interruption', function () {
    $this->service->creer(donneesInterim(['date_debut' => '2026-06-01', 'date_fin_prevue' => '2026-07-01']));
    $this->service->cloturer(InterimInterne::sole(), CauseFinInterim::RETOUR_TITULAIRE, '2026-07-01');

    $interim = $this->service->creer(donneesInterim());

    expect($interim->date_limite->toDateString())->toBe('2026-11-18');
});

it('RG-INT-03 : n\'applique pas le cumul lorsque le paramètre est désactivé', function () {
    config(['interim.cumul_interims_meme_poste' => false]);
    $this->service->creer(donneesInterim(['date_debut' => '2026-08-01', 'date_fin_prevue' => '2026-09-01']));
    $this->service->cloturer(InterimInterne::sole(), CauseFinInterim::RETOUR_TITULAIRE, '2026-09-01');

    $interim = $this->service->creer(donneesInterim(['date_debut' => '2026-09-02', 'date_fin_prevue' => '2026-11-01']));

    expect($interim->date_limite->toDateString())->toBe('2026-11-01');
});

// --- RG-INT-04 ---

it('RG-INT-04 : permet de dépasser 2 mois pour chaque motif éligible', function (MotifInterim $motif, array $extra) {
    $interim = $this->service->creer(donneesInterim(array_merge([
        'motif' => $motif->value,
        'date_fin_prevue' => '2026-12-19',
    ], $extra)));

    expect($interim->statut)->toBe(StatutInterim::PROLONGE);
})->with([
    'maladie ou accident' => [MotifInterim::MALADIE_ACCIDENT, []],
    'congé annuel' => [MotifInterim::CONGE_ANNUEL, ['date_fin_motif' => '2026-12-31']],
    'congé de maternité' => [MotifInterim::CONGE_MATERNITE, ['date_fin_motif' => '2026-12-31']],
    'détention préventive' => [MotifInterim::DETENTION_PREVENTIVE, []],
    'AT / MP' => [MotifInterim::AT_MP, ['date_fin_motif' => '2027-01-31']],
]);

it('RG-INT-04 : plafonne à 2 mois chaque motif non éligible', function (MotifInterim $motif) {
    $erreurs = erreursDe(fn () => $this->service->creer(donneesInterim([
        'motif' => $motif->value,
        'date_fin_prevue' => '2026-12-19',
    ])));

    expect($erreurs['date_fin_prevue'][0])->toContain('ne permet pas de prolongation');
})->with([
    'poste vacant' => [MotifInterim::POSTE_VACANT],
    'réorganisation' => [MotifInterim::REORGANISATION],
    'autre' => [MotifInterim::AUTRE],
]);

// --- RG-INT-05 ---

it('RG-INT-05 : plafonne la maladie à 6 mois (Code art. 65 c ; CCIT art. 14)', function () {
    $interim = $this->service->creer(donneesInterim(['motif' => MotifInterim::MALADIE_ACCIDENT->value, 'date_fin_prevue' => '2027-03-18']));
    expect($interim->date_limite->toDateString())->toBe('2027-03-18');

    $autrePoste = Poste::where('intitule', 'Comptable')->firstOrFail();
    $autrePoste->update(['categorie_classification_id' => $this->categorieB->id]);

    $erreurs = erreursDe(fn () => $this->service->creer(donneesInterim([
        'motif' => MotifInterim::MALADIE_ACCIDENT->value,
        'poste_occupe_id' => $autrePoste->id,
        'date_fin_prevue' => '2027-03-20',
    ])));
    expect($erreurs['date_fin_prevue'][0])->not->toContain('art.');
});

it('RG-INT-05 : fait courir le délai maladie depuis le début de l\'absence du titulaire', function () {
    $absence = absenceTitulaire('MAL', '2026-08-01 00:00:00');

    $erreurs = erreursDe(fn () => $this->service->creer(donneesInterim([
        'motif' => MotifInterim::MALADIE_ACCIDENT->value,
        'absence_titulaire_id' => $absence->id,
        'date_fin_prevue' => '2027-02-01',
    ])));

    expect($erreurs['date_fin_prevue'][0])->toContain('au plus tard le 31/01/2027');
});

it('RG-INT-05 : plafonne la détention préventive à 6 mois (Code art. 65 h)', function () {
    $erreurs = erreursDe(fn () => $this->service->creer(donneesInterim([
        'motif' => MotifInterim::DETENTION_PREVENTIVE->value,
        'date_fin_prevue' => '2027-03-20',
    ])));

    expect($erreurs['date_fin_prevue'][0])->not->toContain('art.');
});

it('RG-INT-05 : exige la fin du congé du titulaire et la retient comme plafond', function () {
    $erreurs = erreursDe(fn () => $this->service->creer(donneesInterim([
        'motif' => MotifInterim::CONGE_MATERNITE->value,
        'date_fin_prevue' => '2026-12-19',
    ])));
    expect($erreurs)->toHaveKey('date_fin_motif');

    $erreurs = erreursDe(fn () => $this->service->creer(donneesInterim([
        'motif' => MotifInterim::CONGE_MATERNITE->value,
        'date_fin_motif' => '2026-12-15',
        'date_fin_prevue' => '2026-12-19',
    ])));
    expect($erreurs['date_fin_prevue'][0])->toContain('au plus tard le 15/12/2026');
});

it('RG-INT-05 : reprend la fin du congé depuis l\'absence du titulaire', function () {
    $absence = absenceTitulaire('CA', '2026-09-15 00:00:00', '2026-12-31 00:00:00');

    $interim = $this->service->creer(donneesInterim([
        'motif' => MotifInterim::CONGE_ANNUEL->value,
        'absence_titulaire_id' => $absence->id,
        'date_fin_prevue' => '2026-12-31',
    ]));

    expect($interim->date_limite->toDateString())->toBe('2026-12-31');
});

it('RG-INT-05 : accepte un AT/MP sans date de consolidation connue et retient la consolidation saisie', function () {
    $interim = $this->service->creer(donneesInterim(['motif' => MotifInterim::AT_MP->value, 'date_fin_prevue' => '2027-01-19']));
    expect($interim->date_limite->toDateString())->toBe('2027-01-19');

    $erreurs = erreursDe(fn () => $this->service->prolonger($interim, '2027-03-19', '2027-02-28', piece()));
    expect($erreurs['date_fin_prevue'][0])->toContain('au plus tard le 28/02/2027');
});

// --- Prolongation (RG-INT-03 à 05) ---

it('prolonge un intérim en cours pour motif éligible et historise la décision avec sa pièce', function () {
    $interim = $this->service->creer(donneesInterim(['motif' => MotifInterim::MALADIE_ACCIDENT->value]));

    $prolonge = $this->service->prolonger($interim, '2027-01-19', null, piece(), 'Certificat médical');

    expect($prolonge->statut)->toBe(StatutInterim::PROLONGE);
    expect($prolonge->date_fin_prevue->toDateString())->toBe('2027-01-19');
    expect($prolonge->mouvementCarriere->date_fin_prevue->toDateString())->toBe('2027-01-19');

    $decision = $prolonge->decisions()->where('type_decision', TypeDecisionInterim::PROLONGATION->value)->sole();
    expect($decision->decide_par)->toBe(auth()->id());
    expect($decision->chemin_piece)->not->toBeNull();
    expect($decision->empreinte_sha256)->not->toBeNull();
    Storage::disk('local')->assertExists($decision->chemin_piece);
});

it('refuse de prolonger au-delà de 2 mois un intérim pour motif non éligible', function () {
    $interim = $this->service->creer(donneesInterim(['date_fin_prevue' => '2026-10-19']));

    $erreurs = erreursDe(fn () => $this->service->prolonger($interim, '2026-12-19', null, piece()));

    expect($erreurs['date_fin_prevue'][0])->toContain('ne permet pas de prolongation');
    expect($interim->fresh()->date_fin_prevue->toDateString())->toBe('2026-10-19');
});

// --- RG-INT-07 ---

it('RG-INT-07 : enregistre un avantage saisi à la main avec son accord et son justificatif', function () {
    $interim = $this->service->creer(donneesInterim());
    $rubrique = RubriquePaie::whereNotIn('code', ['SAL-BASE'])->firstOrFail();

    $avantage = $this->service->ajouterAvantage($interim, [
        'rubrique_id' => $rubrique->id,
        'montant_mensuel' => 15000,
        'date_debut' => '2026-09-19',
        'reference_accord' => 'ACC-2026-014',
    ], piece());

    expect($avantage->montant_mensuel)->toBe(15000);
    Storage::disk('local')->assertExists($avantage->chemin_justificatif);
    expect($interim->decisions()->where('type_decision', TypeDecisionInterim::AVANTAGE->value)->count())->toBe(1);
});

it('RG-INT-07 : refuse un avantage sans justificatif ni référence d\'accord', function () {
    $interim = $this->service->creer(donneesInterim());

    $this->postJson(route('carriere.interims.avantages.store', $interim), [
        'rubrique_id' => RubriquePaie::firstOrFail()->id,
        'montant_mensuel' => 15000,
        'date_debut' => '2026-09-19',
    ])->assertStatus(422)->assertJsonValidationErrors(['reference_accord', 'justificatif']);
});

it('RG-INT-07 : refuse de saisir le salaire de base comme avantage', function () {
    $interim = $this->service->creer(donneesInterim());

    $erreurs = erreursDe(fn () => $this->service->ajouterAvantage($interim, [
        'rubrique_id' => RubriquePaie::where('code', 'SAL-BASE')->value('id'),
        'montant_mensuel' => 15000,
        'date_debut' => '2026-09-19',
        'reference_accord' => 'ACC',
    ], piece()));

    expect($erreurs)->toHaveKey('rubrique_id');
});

// --- RG-INT-08 ---

it('RG-INT-08 : reclasse dans la catégorie occupée avec un salaire au moins égal au minimum', function () {
    $interim = $this->service->creer(donneesInterim());
    $position = PositionClassification::where('code', 'B1-A')->firstOrFail();

    $clos = $this->service->deciderFin($interim, DecisionFinInterim::RECLASSEMENT, today()->toDateString(), piece(), $position->id, 140000);

    expect($clos->statut)->toBe(StatutInterim::CLOS);
    expect($clos->decision_fin)->toBe(DecisionFinInterim::RECLASSEMENT);
    expect($clos->decide_par)->toBe(auth()->id());
    expect(SituationCarriere::where('salarie_id', $this->salarie->id)->value('position_classification_id'))->toBe($position->id);
    expect((int) ElementPaieSalarie::where('salarie_id', $this->salarie->id)
        ->whereHas('rubrique', fn ($q) => $q->where('code', 'SAL-BASE'))->value('montant'))->toBe(140000);

    $reclassement = MouvementCarriere::where('salarie_id', $this->salarie->id)
        ->where('type_mouvement', TypeMouvement::RECLASSEMENT->value)->sole();
    expect($reclassement->statut)->toBe(StatutMouvement::EFFECTIF);
    expect($clos->mouvementCarriere->statut)->toBe(StatutMouvement::TERMINE);
});

it('RG-INT-08 : refuse un reclassement sous le minimum de la catégorie', function () {
    $interim = $this->service->creer(donneesInterim());

    $erreurs = erreursDe(fn () => $this->service->deciderFin(
        $interim, DecisionFinInterim::RECLASSEMENT, today()->toDateString(), piece(),
        PositionClassification::where('code', 'B1-A')->value('id'), 139999,
    ));

    expect($erreurs['nouveau_salaire_base'][0])->toContain('140 000 FCFA')->not->toContain('art.');
    expect($interim->fresh()->statut)->toBe(StatutInterim::EN_COURS);
});

it('RG-INT-08 : refuse un reclassement hors de la catégorie occupée', function () {
    $interim = $this->service->creer(donneesInterim());

    $erreurs = erreursDe(fn () => $this->service->deciderFin(
        $interim, DecisionFinInterim::RECLASSEMENT, today()->toDateString(), piece(),
        PositionClassification::where('code', 'A1-B')->value('id'), 500000,
    ));

    expect($erreurs)->toHaveKey('position_cible_id');
});

it('RG-INT-08 : le retour rétablit la situation d\'origine sans la modifier', function () {
    $affectation = Affectation::where('salarie_id', $this->salarie->id)->where('en_cours', true)->sole();
    $position = SituationCarriere::where('salarie_id', $this->salarie->id)->value('position_classification_id');
    $interim = $this->service->creer(donneesInterim());

    $clos = $this->service->deciderFin($interim, DecisionFinInterim::RETOUR, '2026-11-19', piece());

    expect($clos->statut)->toBe(StatutInterim::CLOS);
    expect($clos->decision_fin)->toBe(DecisionFinInterim::RETOUR);
    expect(Affectation::where('salarie_id', $this->salarie->id)->where('en_cours', true)->sole()->id)->toBe($affectation->id);
    expect(SituationCarriere::where('salarie_id', $this->salarie->id)->value('position_classification_id'))->toBe($position);
    expect($clos->decisions()->where('type_decision', TypeDecisionInterim::RETOUR->value)->count())->toBe(1);
});

// --- RG-INT-09 (statut) ---

it('RG-INT-09 : passe un intérim à régulariser sans le clôturer', function () {
    $interim = $this->service->creer(donneesInterim());

    $this->service->passerARegulariser($interim, 'Délai dépassé sans décision');

    expect($interim->fresh()->statut)->toBe(StatutInterim::A_REGULARISER);
    expect($interim->fresh()->date_fin_reelle)->toBeNull();
});

// --- RG-INT-10 ---

it('RG-INT-10 : clôture l\'intérim au retour du titulaire', function () {
    $interim = $this->service->creer(donneesInterim());

    $clos = $this->service->cloturer($interim, CauseFinInterim::RETOUR_TITULAIRE, '2026-10-30');

    expect($clos->statut)->toBe(StatutInterim::CLOS);
    expect($clos->cause_fin)->toBe(CauseFinInterim::RETOUR_TITULAIRE);
    expect($clos->date_fin_reelle->toDateString())->toBe('2026-10-30');
    expect($clos->mouvementCarriere->statut)->toBe(StatutMouvement::TERMINE);
});

it('RG-INT-10 : exige une décision au lieu d\'une clôture après la date limite', function () {
    $interim = $this->service->creer(donneesInterim());

    $erreurs = erreursDe(fn () => $this->service->cloturer($interim, CauseFinInterim::FIN_MOTIF, '2026-11-20'));

    expect($erreurs['date_fin_reelle'][0])->toContain('décision de reclassement ou de retour est obligatoire');
});

// --- Écrans ---

it('crée un intérim depuis le formulaire et renvoie les refus en français', function () {
    $this->postJson(route('carriere.interims.store'), donneesInterim(['poste_occupe_id' => $this->posteEgal->id]))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['poste_occupe_id']);

    $this->postJson(route('carriere.interims.store'), donneesInterim())
        ->assertOk()
        ->assertJsonPath('message', 'Intérim créé.');

    $interim = InterimInterne::sole();
    $this->get(route('carriere.interims.show', $interim))->assertOk()->assertSee($this->posteSuperieur->intitule);
    $this->get(route('carriere.interims.index'))->assertOk()->assertSee($this->salarie->nom_complet);
    $this->get(route('carriere.interims.create'))->assertOk();
});

it('prolonge, décide et télécharge une pièce depuis les écrans', function () {
    $interim = $this->service->creer(donneesInterim(['motif' => MotifInterim::MALADIE_ACCIDENT->value]));

    $this->post(route('carriere.interims.prolonger', $interim), [
        'date_fin_prevue' => '2027-01-19',
        'piece' => piece(),
    ], ['Accept' => 'application/json'])->assertOk();

    $decision = $interim->decisions()->where('type_decision', TypeDecisionInterim::PROLONGATION->value)->sole();
    $this->get(route('carriere.interims.piece', [$interim, $decision]))->assertOk();

    $this->post(route('carriere.interims.decider', $interim), [
        'decision' => DecisionFinInterim::RETOUR->value,
        'date_effet' => '2026-12-01',
        'piece' => piece(),
    ], ['Accept' => 'application/json'])->assertOk();

    expect($interim->fresh()->statut)->toBe(StatutInterim::CLOS);
});
