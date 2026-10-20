<?php

/*
| Tests de caractérisation (lot 0 de la refonte des intérims) : ils figent le
| comportement actuel que la refonte conserve. La fermeture de l'affectation
| permanente à la clôture (CloturerInterim) n'est volontairement pas figée :
| elle contredit RG-INT-08 et sera corrigée.
*/

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Carriere\Actions\CloturerInterim;
use App\Domain\Carriere\Actions\ProlongerInterim;
use App\Domain\Carriere\Enums\StatutMouvement;
use App\Domain\Carriere\Enums\TypeMouvement;
use App\Domain\Carriere\Models\MouvementCarriere;
use App\Domain\Organisation\Models\Poste;
use App\Domain\Paie\Models\BulletinPaie;
use App\Domain\Paie\Models\PeriodePaie;
use App\Domain\Paie\Services\MoteurPaie;
use App\Domain\Personnel\Models\Salarie;

beforeEach(function () {
    $this->seed();
    $this->admin = Utilisateur::where('role', Utilisateur::ROLE_SUPER_ADMIN)->first();
    $this->actingAs($this->admin);

    $this->salarie = Salarie::first();
    $this->poste = Poste::first();
});

function creerInterimEffectif(array $attributs = []): MouvementCarriere
{
    return MouvementCarriere::factory()->interim()->effectif()->create(array_merge([
        'entreprise_id' => 1,
        'salarie_id' => test()->salarie->id,
        'poste_cible_id' => test()->poste->id,
        'date_fin_prevue' => now()->addMonth(),
    ], $attributs));
}

it('crée un intérim comme mouvement de carrière de type intérim', function () {
    $this->post(route('carriere.interims.store'), [
        'salarie_id' => $this->salarie->id,
        'type_mouvement' => TypeMouvement::INTERIM->value,
        'poste_cible_id' => $this->poste->id,
        'date_effet' => now()->format('Y-m-d'),
        'date_fin_prevue' => now()->addMonth()->format('Y-m-d'),
        'motif' => 'Caractérisation',
    ])->assertRedirect();

    $interim = MouvementCarriere::where('motif', 'Caractérisation')->sole();
    expect($interim->type_mouvement)->toBe(TypeMouvement::INTERIM);
    expect($interim->salarie_id)->toBe($this->salarie->id);
    expect($interim->poste_cible_id)->toBe($this->poste->id);
});

it('ne liste que les intérims dans le registre des intérims', function () {
    $interim = creerInterimEffectif(['motif' => 'Intérim listé']);
    MouvementCarriere::factory()->create([
        'entreprise_id' => 1,
        'salarie_id' => $this->salarie->id,
        'type_mouvement' => TypeMouvement::AFFECTATION->value,
        'motif' => 'Affectation non listée',
    ]);

    $this->get(route('carriere.interims.index'))
        ->assertOk()
        ->assertViewHas('interims', fn ($liste) => $liste->contains('id', $interim->id)
            && $liste->every(fn ($m) => $m->type_mouvement === TypeMouvement::INTERIM));
});

it('refuse d\'afficher un mouvement qui n\'est pas un intérim', function () {
    $affectation = MouvementCarriere::factory()->create([
        'entreprise_id' => 1,
        'salarie_id' => $this->salarie->id,
        'type_mouvement' => TypeMouvement::AFFECTATION->value,
    ]);

    $this->get(route('carriere.interims.show', $affectation))->assertNotFound();
});

// Seul un intérim non encore effectif est prolongeable aujourd'hui (EFFECTIF est un statut
// final) : ce blocage contredit RG-INT-04 et n'est donc pas figé ici.
it('prolonge un intérim programmé et trace l\'ancienne et la nouvelle date de fin', function () {
    $interim = creerInterimEffectif([
        'statut' => StatutMouvement::PROGRAMME->value,
        'date_effet' => now()->addDay(),
    ]);
    $nouvelleFin = now()->addMonths(2)->format('Y-m-d');

    app(ProlongerInterim::class)->executer($interim, $nouvelleFin, 'Prolongation');

    expect($interim->fresh()->date_fin_prevue->format('Y-m-d'))->toBe($nouvelleFin);
    $this->assertDatabaseHas('journal_audit', ['action' => 'carriere.interim.prolonge']);
});

it('refuse une prolongation qui n\'est pas postérieure à la fin actuelle', function () {
    $interim = creerInterimEffectif();

    app(ProlongerInterim::class)->executer($interim, now()->subDay()->format('Y-m-d'));
})->throws(DomainException::class);

it('refuse de prolonger un intérim clôturé', function () {
    $interim = creerInterimEffectif(['statut' => StatutMouvement::TERMINE->value]);

    app(ProlongerInterim::class)->executer($interim, now()->addMonths(2)->format('Y-m-d'));
})->throws(DomainException::class);

it('clôture un intérim avec sa date de fin réelle et son motif', function () {
    $interim = creerInterimEffectif();

    app(CloturerInterim::class)->executer($interim, now()->format('Y-m-d'), 'Retour du titulaire');

    $interim->refresh();
    expect($interim->statut)->toBe(StatutMouvement::TERMINE);
    expect($interim->date_fin_reelle->format('Y-m-d'))->toBe(now()->format('Y-m-d'));
    expect($interim->motif_cloture)->toBe('Retour du titulaire');
});

it('refuse de prolonger ou clôturer un mouvement qui n\'est pas un intérim', function () {
    $affectation = MouvementCarriere::factory()->effectif()->create([
        'entreprise_id' => 1,
        'salarie_id' => $this->salarie->id,
        'type_mouvement' => TypeMouvement::AFFECTATION->value,
    ]);

    expect(fn () => app(ProlongerInterim::class)->executer($affectation, now()->addMonth()->format('Y-m-d')))
        ->toThrow(DomainException::class);
    expect(fn () => app(CloturerInterim::class)->executer($affectation, now()->format('Y-m-d')))
        ->toThrow(DomainException::class);
});

// RG-INT-02 : pendant les deux premiers mois, la paie reste celle de la catégorie d'origine.
it('n\'a aucun effet sur le bulletin pendant un intérim de moins de deux mois', function () {
    $periode = PeriodePaie::where('statut', 'open')->firstOrFail();
    $moteur = app(MoteurPaie::class);
    $brutDe = fn () => (float) BulletinPaie::where('periode_id', $periode->id)
        ->where('salarie_id', $this->salarie->id)->value('montant_brut');

    $moteur->calculer($periode);
    $brutSansInterim = $brutDe();

    creerInterimEffectif(['date_effet' => now()->subWeek()]);
    $periode->update(['statut' => 'open']);
    $moteur->calculer($periode);

    expect($brutDe())->toBe($brutSansInterim);
});
