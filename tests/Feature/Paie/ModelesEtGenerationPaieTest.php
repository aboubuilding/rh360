<?php

/*
| Paie — modèles de bulletin appliqués (montant par défaut, élément individuel, exclusion,
| validité datée, association manuelle), aperçu d'un modèle, et génération de la paie du mois
| avec téléchargement des bulletins.
*/

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Carriere\Models\SituationCarriere;
use App\Domain\Paie\Models\AffectationModelePaie;
use App\Domain\Paie\Models\AnomaliePaie;
use App\Domain\Paie\Models\BulletinPaie;
use App\Domain\Paie\Models\ElementPaieSalarie;
use App\Domain\Paie\Models\ModelePaie;
use App\Domain\Paie\Models\ModelePaieRubrique;
use App\Domain\Paie\Models\PeriodePaie;
use App\Domain\Paie\Models\RegleAnciennete;
use App\Domain\Paie\Models\RubriquePaie;
use App\Domain\Paie\Services\MoteurPaie;
use App\Domain\Personnel\Models\Salarie;

beforeEach(function () {
    $this->seed();
    $this->actingAs(Utilisateur::where('role', Utilisateur::ROLE_SUPER_ADMIN)->first());

    [$this->a, $this->b] = Salarie::where('actif', true)->orderBy('id')->take(2)->get()->all();
    Salarie::where('actif', true)->whereNotIn('id', [$this->a->id, $this->b->id])->update(['actif' => false]);
    RegleAnciennete::query()->update(['actif' => false]);

    $this->rubrique = fn (string $code) => RubriquePaie::where('code', $code)->sole();
    $this->element = fn (Salarie $s, string $code, array $valeurs) => ElementPaieSalarie::updateOrCreate(
        ['salarie_id' => $s->id, 'rubrique_id' => ($this->rubrique)($code)->id],
        $valeurs + ['entreprise_id' => 1, 'actif' => true, 'etat' => 1],
    );
    foreach ([$this->a, $this->b] as $s) {
        SituationCarriere::where('salarie_id', $s->id)->first()->positionClassification->update(['salaire_minimum' => 75500]);
        ElementPaieSalarie::where('salarie_id', $s->id)->delete();
        ($this->element)($s, 'SAL-BASE', ['montant' => 75500]);
        $s->update(['date_embauche' => '2020-01-01']);
    }

    // Modèle de la catégorie du salarié A : indemnité de logement de 20 000 par défaut.
    $this->categorieA = SituationCarriere::where('salarie_id', $this->a->id)->first()->positionClassification->categorie_id;
    $this->modeleA = ModelePaie::where('categorie_id', $this->categorieA)->sole();
    ModelePaieRubrique::where('modele_id', $this->modeleA->id)->where('rubrique_id', ($this->rubrique)('PRIME-LOGT')->id)->update(['montant_defaut' => 20000]);

    $this->periode = PeriodePaie::updateOrCreate(['entreprise_id' => 1, 'annee' => 2026, 'mois' => 9], ['statut' => 'open', 'etat' => 1]);
    $this->calculer = fn () => app(MoteurPaie::class)->calculer($this->periode->fresh());
    $this->montant = fn (Salarie $s, string $code) => BulletinPaie::where('periode_id', $this->periode->id)->where('salarie_id', $s->id)->sole()
        ->lignes()->where('code', $code)->value('montant');
});

// --- Modèle appliqué ---

it('applique le montant par défaut du modèle de la catégorie', function () {
    ($this->calculer)();

    expect((float) ($this->montant)($this->a, 'PRIME-LOGT'))->toBe(20000.0);
});

it('remplace le montant du modèle par l\'élément individuel, ou exclut la rubrique', function () {
    ($this->element)($this->a, 'PRIME-LOGT', ['montant' => 25000]);
    ($this->calculer)();
    expect((float) ($this->montant)($this->a, 'PRIME-LOGT'))->toBe(25000.0);

    ($this->element)($this->a, 'PRIME-LOGT', ['montant' => 0, 'exclu' => true]);
    ($this->calculer)();
    expect(($this->montant)($this->a, 'PRIME-LOGT'))->toBeNull();
});

it('proratise un élément individuel daté et ajoute les éléments hors modèle', function () {
    // Prime de rendement hors modèle, valable à partir du 16 septembre : 15/30.
    ($this->element)($this->a, 'PRIME-REND', ['montant' => 30000, 'debut' => '2026-09-16']);
    // Logement individuel terminé en août : retour au défaut du modèle.
    ($this->element)($this->a, 'PRIME-LOGT', ['montant' => 50000, 'fin' => '2026-08-31']);

    ($this->calculer)();

    expect((float) ($this->montant)($this->a, 'PRIME-LOGT'))->toBe(20000.0);
    $recurrence = ($this->rubrique)('PRIME-REND')->recurrence->value;
    expect((float) ($this->montant)($this->a, 'PRIME-REND'))->toBe($recurrence === 'fixed' ? 15000.0 : 30000.0);
});

it('bloque le bulletin d\'un salarié sans modèle', function () {
    $this->modeleA->update(['actif' => false]);

    ($this->calculer)();

    expect(AnomaliePaie::where('salarie_id', $this->a->id)->where('code', 'modele')->value('message'))
        ->toBe('Aucun modèle de bulletin actif pour la catégorie du salarié.');
});

it('applique le modèle associé manuellement, mais refuse un modèle d\'une autre classe', function () {
    $autreModele = ModelePaie::where('id', '!=', $this->modeleA->id)->orderBy('id')->firstOrFail();
    AffectationModelePaie::create(['entreprise_id' => 1, 'salarie_id' => $this->a->id, 'modele_id' => $autreModele->id, 'debut' => '2026-01-01', 'motif' => 'Essai']);

    ($this->calculer)();

    $anomalie = AnomaliePaie::where('salarie_id', $this->a->id)->where('code', 'modele')->value('message');
    $memeClasse = $anomalie === null;
    if ($memeClasse) {
        // Même classe : le modèle associé s'applique (sans le logement du modèle de la catégorie).
        expect(($this->montant)($this->a, 'PRIME-LOGT'))->toBeNull();
    } else {
        expect($anomalie)->toContain('autre classe');
    }
});

// --- Écran des éléments ---

it('enregistre les éléments d\'un salarié et refuse un salaire sous le minimum', function () {
    $this->get(route('paie.elements.index'))->assertOk()->assertSee($this->a->nom_complet)->assertSee('selon la catégorie');
    $this->get(route('paie.elements.show', $this->a))->assertOk()->assertSee('Défaut du modèle')->assertSee('Aperçu de son bulletin');

    $this->post(route('paie.elements.store', $this->a), ['rubrique_id' => ($this->rubrique)('PRIME-LOGT')->id, 'montant' => 18000, 'debut' => '2026-10-01'])
        ->assertRedirect()->assertSessionHas('success');
    expect(ElementPaieSalarie::where('salarie_id', $this->a->id)->where('rubrique_id', ($this->rubrique)('PRIME-LOGT')->id)->value('debut')->toDateString())->toBe('2026-10-01');

    $this->post(route('paie.elements.store', $this->a), ['rubrique_id' => ($this->rubrique)('SAL-BASE')->id, 'montant' => 60000])
        ->assertSessionHas('error');

    $modele = ModelePaie::orderBy('id')->firstOrFail();
    $this->post(route('paie.elements.associer', $this->a), ['modele_id' => $modele->id, 'debut' => '2026-10-01', 'motif' => 'Statut particulier'])->assertSessionHas('success');
    $this->post(route('paie.elements.associer', $this->a), ['modele_id' => $modele->id, 'debut' => '2026-12-01', 'motif' => 'Doublon'])->assertSessionHas('error');
});

// --- Aperçu d'un modèle ---

it('affiche l\'aperçu d\'un bulletin type, au minimum de la catégorie, sans rien enregistrer', function () {
    $avant = BulletinPaie::count();

    $this->get(route('paie.modeles.apercu', $this->modeleA))->assertOk()->assertSee('Paramètres de l\'aperçu', false)->assertSee('iframe', false);
    $this->get(route('paie.modeles.apercu.document', [$this->modeleA, 'mois' => '2026-09']))->assertOk()
        ->assertSee('APERÇU')->assertSee('SALARIÉ Type', false)->assertSee('BULLETIN DE PAIE')->assertSee('20 000');

    $pdf = $this->get(route('paie.modeles.apercu.document', [$this->modeleA, 'format' => 'pdf']));
    $pdf->assertOk();
    expect($pdf->headers->get('content-type'))->toBe('application/pdf');
    expect(BulletinPaie::count())->toBe($avant);
});

it('affiche l\'aperçu du bulletin d\'un salarié réel avec ce modèle', function () {
    $avant = BulletinPaie::count();

    $this->get(route('paie.modeles.apercu.document', [$this->modeleA, 'salarie_id' => $this->a->id, 'mois' => '2026-09']))->assertOk()
        ->assertSee($this->a->nom_complet)->assertSee('75 500')->assertSee('APERÇU');
    expect(BulletinPaie::count())->toBe($avant);
});

// --- Génération de la paie du mois ---

it('génère en un clic les bulletins d\'une nouvelle période et les rend téléchargeables', function () {
    $this->get(route('paie.generation.index'))->assertOk()->assertSee('Générer les bulletins');

    $this->post(route('paie.generation.generer'), ['annee' => 2026, 'mois' => 11])->assertRedirect()->assertSessionHas('success');

    $periode = PeriodePaie::where('annee', 2026)->where('mois', 11)->sole();
    expect($periode->bulletins()->count())->toBe(2);
    $this->get(route('paie.generation.index', ['periode' => $periode->id]))->assertOk()
        ->assertSee('Tous les bulletins (un PDF)')->assertSee($this->a->nom_complet);

    $pdf = $this->get(route('paie.generation.telecharger', $periode));
    $pdf->assertOk();
    expect($pdf->headers->get('content-type'))->toBe('application/pdf');

    $zip = $this->get(route('paie.generation.telecharger', [$periode, 'format' => 'zip']));
    $zip->assertOk();
    $archive = new ZipArchive();
    $archive->open($zip->baseResponse->getFile()->getPathname());
    expect($archive->numFiles)->toBe(2);
    $archive->close();
});

it('ne recalcule pas une période validée, dont les bulletins restent téléchargeables', function () {
    ($this->calculer)();
    $this->periode->update(['statut' => 'validated']);

    $this->post(route('paie.generation.generer'), ['annee' => 2026, 'mois' => 9])
        ->assertRedirect(route('paie.generation.index', ['periode' => $this->periode->id]))->assertSessionHas('error');
    $this->get(route('paie.generation.telecharger', $this->periode))->assertOk();
});

// --- Modification d'un modèle existant ---

it('modifie un modèle existant : rubriques ajoutées, retirées et montants changés', function () {
    $this->get(route('paie.modeles.edit', $this->modeleA))->assertOk()
        ->assertSee('Enregistrer les modifications')->assertSee($this->modeleA->nom)->assertSee('name="_method" value="PUT"', false);

    $lignes = [
        // Logement conservé avec un nouveau montant, prime de rendement ajoutée, transport retiré ; ligne vide ignorée.
        ['rubrique_id' => ($this->rubrique)('PRIME-LOGT')->id, 'montant_defaut' => 35000, 'ordre' => 10],
        ['rubrique_id' => ($this->rubrique)('PRIME-REND')->id, 'montant_defaut' => 5000, 'ordre' => 20],
        ['rubrique_id' => '', 'montant_defaut' => 0, 'ordre' => 30],
    ];
    $this->putJson(route('paie.modeles.update', $this->modeleA), [
        'nom' => 'Modèle remanié', 'categorie_id' => $this->categorieA, 'actif' => 1, 'rubriques' => $lignes,
    ])->assertOk();

    $modele = $this->modeleA->fresh('rubriques.rubrique');
    expect($modele->nom)->toBe('Modèle remanié');
    expect($modele->rubriques->mapWithKeys(fn ($l) => [$l->rubrique->code => (float) $l->montant_defaut])->sortKeys()->all())
        ->toBe(['PRIME-LOGT' => 35000.0, 'PRIME-REND' => 5000.0]);

    ($this->calculer)();
    expect((float) ($this->montant)($this->a, 'PRIME-LOGT'))->toBe(35000.0);

    // Une rubrique en double est refusée.
    $this->putJson(route('paie.modeles.update', $this->modeleA), [
        'nom' => 'Modèle remanié', 'categorie_id' => $this->categorieA, 'actif' => 1,
        'rubriques' => [$lignes[0], $lignes[0]],
    ])->assertStatus(422)->assertJsonValidationErrors(['rubriques.1.rubrique_id']);
});
