<?php

/*
| Paie — effet de chaque rubrique sur le salaire, connu avant de l'ajouter : gain (ajoute au
| brut), remboursement de frais (ajouté au net, hors brut), retenue (retranchée du net, dans
| les seuls cas prévus par la loi, Code art. 173 et 175), cotisation salariale.
*/

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Paie\Models\ModelePaie;
use App\Domain\Paie\Models\RubriquePaie;

beforeEach(function () {
    $this->seed();
    $this->actingAs(Utilisateur::where('role', Utilisateur::ROLE_SUPER_ADMIN)->first());
    $this->rubrique = fn (array $valeurs) => $valeurs + [
        'nom' => 'Rubrique de test', 'recurrence' => 'fixed', 'mode_calcul' => 'amount',
        'traitement_fiscal' => 'taxable', 'imposable' => 1, 'soumis_cotisation' => 1, 'actif' => 1,
    ];
});

it('enregistre l\'effet choisi : remboursement de frais hors brut', function () {
    $this->postJson(route('paie.rubriques.store'), ($this->rubrique)(['code' => 'IND-PANIER', 'effet' => 'frais']))->assertOk();

    $r = RubriquePaie::where('code', 'IND-PANIER')->sole();
    expect([$r->nature->value, $r->est_frais])->toBe(['gain', true]);
    expect($r->effetSalaire()['libelle'])->toContain('hors brut');
});

it('exige un cas de retenue autorisé par la loi', function () {
    $this->postJson(route('paie.rubriques.store'), ($this->rubrique)(['code' => 'RET-RETARD', 'effet' => 'retenue']))
        ->assertStatus(422)->assertJsonValidationErrors(['type_retenue']);

    $this->postJson(route('paie.rubriques.store'), ($this->rubrique)(['code' => 'MUTU', 'effet' => 'retenue', 'type_retenue' => 'mutuelle']))->assertOk();
    expect(RubriquePaie::where('code', 'MUTU')->sole()->type_retenue->value)->toBe('mutuelle');
});

it('affiche l\'effet de chaque rubrique dans la liste et dans le choix des modèles', function () {
    $this->get(route('paie.rubriques.index'))->assertOk()
        ->assertSee('Effet sur le salaire')->assertSee('Ajoute au salaire brut')->assertSee('hors brut et hors cotisations')
        ->assertSee('Retranche du net à payer');

    $this->get(route('paie.modeles.edit', ModelePaie::orderBy('id')->first()))->assertOk()
        ->assertSee('(+) SAL-BASE', false)->assertSee('(−) PRET', false);
});
