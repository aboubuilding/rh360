<?php

/*
| Lot 6 — prêt de main-d'œuvre à but non lucratif (Code du travail, art. 8 — RG-PMO-01 à RG-PMO-05).
| Grille de démonstration : catégories A (ordre 1) < B (ordre 2) < C (ordre 3).
*/

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Carriere\Enums\StatutMouvement;
use App\Domain\Carriere\Enums\TypeMouvement;
use App\Domain\Carriere\Models\MouvementCarriere;
use App\Domain\Carriere\Models\SituationCarriere;
use App\Domain\Classification\Models\CategorieClassification;
use App\Domain\Classification\Models\PositionClassification;
use App\Domain\Interim\Enums\ReponseSalarie;
use App\Domain\Interim\Enums\StatutPret;
use App\Domain\Interim\Enums\TypeDecisionInterim;
use App\Domain\Interim\Enums\TypePiecePret;
use App\Domain\Interim\Models\AlerteInterim;
use App\Domain\Interim\Models\PretMainOeuvre;
use App\Domain\Interim\Services\BalayeurInterims;
use App\Domain\Interim\Services\PretMainOeuvreService;
use App\Domain\Organisation\Models\Poste;
use App\Domain\Paie\Models\BulletinPaie;
use App\Domain\Paie\Models\ElementPaieSalarie;
use App\Domain\Paie\Models\PeriodePaie;
use App\Domain\Paie\Services\MoteurPaie;
use App\Domain\Personnel\Models\Affectation;
use App\Domain\Personnel\Models\Salarie;
use App\Domain\Pilotage\Services\CalculateurIndicateursPersonnel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed();
    $this->actingAs(Utilisateur::where('role', Utilisateur::ROLE_SUPER_ADMIN)->first());
    Storage::fake('local');

    $this->salarie = Salarie::orderBy('id')->firstOrFail();
    $this->affectation = Affectation::where('salarie_id', $this->salarie->id)->where('en_cours', true)->sole();
    $this->categorieB = CategorieClassification::where('code', 'B')->firstOrFail();
    $this->posteOrigine = Poste::findOrFail($this->affectation->poste_id);
    $this->posteOrigine->update(['categorie_classification_id' => $this->categorieB->id]);
    SituationCarriere::where('salarie_id', $this->salarie->id)
        ->update(['position_classification_id' => PositionClassification::where('code', 'B1-A')->value('id')]);
    $this->salaireBase = fn () => (int) ElementPaieSalarie::where('salarie_id', $this->salarie->id)
        ->whereHas('rubrique', fn ($q) => $q->where('code', 'SAL-BASE'))->value('montant');

    $this->service = app(PretMainOeuvreService::class);
});

function nouveauPret(array $surcharges = []): PretMainOeuvre
{
    return test()->service->creer(array_merge([
        'salarie_id' => test()->salarie->id,
        'utilisatrice_raison_sociale' => 'Filiale Kara SA',
        'date_debut' => today()->addDays(10)->toDateString(),
        'date_fin' => today()->addMonths(3)->toDateString(),
        'qualification' => 'Technicien de maintenance',
        'mode_calcul_refacturation' => 'Salaire brut + charges sociales patronales + frais de mission sur justificatifs',
        'travail_confie' => 'Maintenance préventive de la ligne de conditionnement',
        'horaires' => 'Lundi au vendredi, 7 h 30 – 16 h 30',
        'lieu' => 'Site de Kara',
        'caracteristiques_poste' => 'Travail posté, habilitation électrique requise',
    ], $surcharges));
}

function fichierSigne(): UploadedFile
{
    return UploadedFile::fake()->create('document-signe.pdf', 20, 'application/pdf');
}

function deposerLesTroisPieces(PretMainOeuvre $pret): void
{
    foreach (TypePiecePret::cases() as $type) {
        test()->service->ajouterPiece($pret, $type, $pret->date_debut->copy()->subDays(2)->toDateString(), fichierSigne());
    }
}

function pretEnCours(array $surcharges = []): PretMainOeuvre
{
    $pret = nouveauPret(array_merge(['date_debut' => today()->toDateString()], $surcharges));
    deposerLesTroisPieces($pret);

    return test()->service->demarrer($pret->fresh());
}

function erreursPret(callable $action): array
{
    try {
        $action();
    } catch (ValidationException $e) {
        return $e->errors();
    }

    throw new RuntimeException('Une ValidationException était attendue.');
}

// --- RG-PMO-01 ---

it('RG-PMO-01 : alerte, sans bloquer, en cas de marge sur la refacturation', function () {
    $pret = pretEnCours();

    $avecMarge = $this->service->enregistrerRefacturation($pret, [
        'periode_debut' => today()->toDateString(), 'periode_fin' => today()->endOfMonth()->toDateString(),
        'salaires' => 200000, 'charges_sociales' => 40000, 'frais_professionnels' => 10000, 'montant_refacture' => 275000,
    ]);
    $sansMarge = $this->service->enregistrerRefacturation($pret, [
        'periode_debut' => today()->toDateString(), 'periode_fin' => today()->endOfMonth()->toDateString(),
        'salaires' => 200000, 'charges_sociales' => 40000, 'frais_professionnels' => 10000, 'montant_refacture' => 250000,
    ]);

    expect($avecMarge->exists)->toBeTrue();
    expect($avecMarge->alerte_marge)->toContain('Marge de 25 000 FCFA')->toContain('250 000 FCFA')->not->toContain('art.');
    expect($sansMarge->alerte_marge)->toBeNull();
    expect($sansMarge->baseRefacturable())->toBe(250000);
});

// --- RG-PMO-02 ---

it('RG-PMO-02 : refuse de démarrer le prêt tant que les trois pièces ne sont pas réunies', function () {
    $pret = nouveauPret();
    $this->service->ajouterPiece($pret, TypePiecePret::ACCORD_SALARIE, today()->toDateString(), fichierSigne());

    $erreurs = erreursPret(fn () => $this->service->demarrer($pret->fresh()));

    expect($erreurs['pieces'][0])->toContain('Prêt refusé : pièces obligatoires manquantes')
        ->toContain('Convention de mise à disposition')->toContain('Avenant au contrat signé')
        ->not->toContain('Accord écrit')->not->toContain('art.');
    expect($pret->fresh()->statut)->toBe(StatutPret::PROJET);
});

it('RG-PMO-02 : refuse une pièce signée après la date de début', function () {
    $pret = nouveauPret();

    $erreurs = erreursPret(fn () => $this->service->ajouterPiece($pret, TypePiecePret::CONVENTION,
        $pret->date_debut->copy()->addDay()->toDateString(), fichierSigne()));

    expect($erreurs['date_signature'][0])->toContain('signée avant le début du prêt');
});

it('RG-PMO-02 : démarre le prêt une fois les trois pièces réunies et l\'historise', function () {
    $pret = nouveauPret();
    deposerLesTroisPieces($pret);

    $demarre = $this->service->demarrer($pret->fresh());

    expect($demarre->statut)->toBe(StatutPret::EN_COURS);
    expect($demarre->reponse_salarie)->toBe(ReponseSalarie::ACCEPTE);
    expect($demarre->pieces()->count())->toBe(3);
    expect($demarre->pieces->every(fn ($p) => $p->empreinte_sha256 !== null))->toBeTrue();
    expect($demarre->decisions()->where('type_decision', TypeDecisionInterim::PIECE->value)->count())->toBe(3);
    expect($demarre->mouvementCarriere->type_mouvement)->toBe(TypeMouvement::MISE_A_DISPOSITION);
});

it('RG-PMO-02 : exige le contenu de la convention et de l\'avenant', function () {
    $this->postJson(route('carriere.interims.prets.store'), ['salarie_id' => $this->salarie->id])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['qualification', 'mode_calcul_refacturation', 'travail_confie', 'horaires', 'lieu', 'caracteristiques_poste', 'date_debut', 'date_fin']);
});

// --- RG-PMO-03 ---

it('RG-PMO-03 : enregistre le refus du salarié sans aucune conséquence', function () {
    $pret = nouveauPret();
    $salaire = ($this->salaireBase)();
    $position = SituationCarriere::where('salarie_id', $this->salarie->id)->value('position_classification_id');
    $mouvements = MouvementCarriere::where('salarie_id', $this->salarie->id)->count();

    $refuse = $this->service->enregistrerRefus($pret, today()->toDateString(), 'Raisons familiales');

    expect($refuse->statut)->toBe(StatutPret::REFUSE_SALARIE);
    expect($refuse->reponse_salarie)->toBe(ReponseSalarie::REFUSE);
    expect($refuse->decisions()->where('type_decision', TypeDecisionInterim::REFUS_SALARIE->value)->sole()->commentaire)
        ->toContain('aucune sanction ni mesure défavorable');
    expect(($this->salaireBase)())->toBe($salaire);
    expect(SituationCarriere::where('salarie_id', $this->salarie->id)->value('position_classification_id'))->toBe($position);
    expect($this->affectation->fresh()->en_cours)->toBeTrue();
    expect(MouvementCarriere::where('salarie_id', $this->salarie->id)->count())->toBe($mouvements);
    expect(fn () => $this->service->demarrer($refuse))->toThrow(ValidationException::class);
});

// --- RG-PMO-04 ---

it('RG-PMO-04 : maintient le salarié dans l\'effectif, la paie et son poste pendant le prêt', function () {
    $effectif = fn () => app(CalculateurIndicateursPersonnel::class)->calculer(1)->firstWhere('cle', 'effectif_actif')->valeur;
    $avant = $effectif();
    $pret = pretEnCours();

    expect($effectif())->toBe($avant);
    expect($this->affectation->fresh()->en_cours)->toBeTrue();
    expect($pret->mouvementCarriere->statut)->toBe(StatutMouvement::EFFECTIF);

    $periode = PeriodePaie::where('statut', 'open')->firstOrFail();
    app(MoteurPaie::class)->calculer($periode);
    $bulletin = BulletinPaie::where('periode_id', $periode->id)->where('salarie_id', $this->salarie->id)->sole();
    expect((float) $bulletin->lignes()->where('code', 'SAL-BASE')->value('montant'))->toBe((float) ($this->salaireBase)());
});

// --- RG-PMO-05 ---

it('RG-PMO-05 : au retour, le salarié retrouve son poste', function () {
    $pret = pretEnCours();

    $termine = $this->service->retour($pret, today()->addMonth()->toDateString());

    expect($termine->statut)->toBe(StatutPret::TERMINE);
    expect($termine->poste_retour_id)->toBe($this->posteOrigine->id);
    expect($termine->mouvementCarriere->statut)->toBe(StatutMouvement::TERMINE);
    expect($this->affectation->fresh()->en_cours)->toBeTrue();
});

it('RG-PMO-05 : accepte le retour sur un poste équivalent et l\'affecte', function () {
    $equivalent = Poste::where('id', '!=', $this->posteOrigine->id)->firstOrFail();
    $equivalent->update(['categorie_classification_id' => $this->categorieB->id]);
    $pret = pretEnCours();

    $this->service->retour($pret, today()->toDateString(), $equivalent->id);

    expect(Affectation::where('salarie_id', $this->salarie->id)->where('en_cours', true)->sole()->poste_id)->toBe($equivalent->id);
});

it('RG-PMO-05 : bloque le retour sur un poste de catégorie inférieure', function () {
    $inferieur = Poste::where('id', '!=', $this->posteOrigine->id)->firstOrFail();
    $inferieur->update(['categorie_classification_id' => CategorieClassification::where('code', 'A')->value('id')]);
    $pret = pretEnCours();

    $erreurs = erreursPret(fn () => $this->service->retour($pret, today()->toDateString(), $inferieur->id));

    expect($erreurs['poste_retour_id'][0])->toContain('catégorie inférieure au poste d\'origine')->not->toContain('art.');
    expect($pret->fresh()->statut)->toBe(StatutPret::EN_COURS);
});

it('RG-PMO-05 : bloque le retour si le salaire de base a baissé pendant le prêt', function () {
    $pret = pretEnCours();
    ElementPaieSalarie::where('salarie_id', $this->salarie->id)->whereHas('rubrique', fn ($q) => $q->where('code', 'SAL-BASE'))
        ->update(['montant' => $pret->salaire_base_origine - 1000]);

    $erreurs = erreursPret(fn () => $this->service->retour($pret, today()->toDateString()));

    expect($erreurs['salaire'][0])->toContain('Aucune baisse de salaire');
});

it('RG-PMO-05 : bloque le retour si la catégorie a baissé pendant le prêt', function () {
    $pret = pretEnCours();
    SituationCarriere::where('salarie_id', $this->salarie->id)
        ->update(['position_classification_id' => PositionClassification::where('code', 'A1-A')->value('id')]);

    $erreurs = erreursPret(fn () => $this->service->retour($pret, today()->toDateString()));

    expect($erreurs['categorie'][0])->toContain('Aucune baisse de catégorie');
});

// --- Alertes ---

it('alerte avant le début d\'un prêt dont les pièces manquent, puis au retour non enregistré', function () {
    $pret = nouveauPret(['date_debut' => '2026-11-10', 'date_fin' => '2026-12-31']);
    $alertes = fn () => AlerteInterim::where('objet_type', $pret->getMorphClass())->where('objet_id', $pret->id)
        ->where('en_cours', true)->pluck('niveau', 'cle')->all();
    $balayer = function (string $date) {
        $this->travelTo($date.' 06:15:00');
        app(BalayeurInterims::class)->balayer(1);
    };

    $balayer('2026-11-02');
    expect($alertes())->toBe([]);
    $balayer('2026-11-03');
    expect($alertes())->toBe(['pieces_pret' => 'warning']);
    $balayer('2026-11-10');
    expect($alertes())->toBe(['pieces_pret' => 'danger']);

    deposerLesTroisPieces($pret);
    $this->service->demarrer($pret->fresh());
    $balayer('2027-01-01');
    expect($alertes())->toBe(['retour_pret' => 'warning']);
});

// --- Écrans ---

it('gère un prêt depuis les écrans', function () {
    $this->get(route('carriere.interims.prets.create'))->assertOk();
    $pret = nouveauPret();

    $this->get(route('carriere.interims.prets.index'))->assertOk()->assertSee($pret->reference);
    $this->get(route('carriere.interims.prets.show', $pret))->assertOk()->assertSee('Pièces obligatoires avant le début');

    $this->postJson(route('carriere.interims.prets.demarrer', $pret))->assertStatus(422);
    foreach (TypePiecePret::cases() as $type) {
        $this->post(route('carriere.interims.prets.piece', $pret), [
            'type_piece' => $type->value, 'date_signature' => today()->toDateString(), 'fichier' => fichierSigne(),
        ], ['Accept' => 'application/json'])->assertOk();
    }
    $this->postJson(route('carriere.interims.prets.demarrer', $pret))->assertOk();
    $this->postJson(route('carriere.interims.prets.refacturation', $pret), [
        'periode_debut' => today()->toDateString(), 'periode_fin' => today()->toDateString(),
        'salaires' => 100, 'charges_sociales' => 10, 'montant_refacture' => 200,
    ])->assertOk()->assertJsonPath('alerte', fn ($a) => str_contains($a, 'Marge'));
    $this->postJson(route('carriere.interims.prets.retour', $pret), ['date_retour' => today()->addMonth()->toDateString()])->assertOk();

    expect($pret->fresh()->statut)->toBe(StatutPret::TERMINE);
});
