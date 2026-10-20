<?php

/*
| Les règles du Code du travail et de la convention collective sont appliquées, mais aucune
| référence aux textes (articles, convention) n'est affichée dans l'application.
| Les tableaux sont lisibles sans défilement horizontal (cellules en retour à la ligne,
| fiches empilées sur petit écran).
*/

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Contrats\Enums\TypeContrat;
use App\Domain\Contrats\Models\Contrat;

beforeEach(function () {
    $this->seed();
    $this->actingAs(Utilisateur::where('role', Utilisateur::ROLE_SUPER_ADMIN)->first());
});

it('n\'affiche aucune référence aux articles du Code du travail ou de la convention', function (string $route) {
    $html = $this->get(route($route))->assertOk()->getContent();
    $texte = strip_tags(preg_replace('#<(script|style)\b[^>]*>.*?</\1>#si', '', $html));

    expect(preg_match('/\bart\.\s*\d|CCIT|Code du travail|Annexe II|décret n°/iu', $texte))->toBe(0);
})->with([
    'carriere.interims.index',
    'carriere.interims.create',
    'carriere.interims.parametres',
    'carriere.interims.missions.index',
    'carriere.interims.missions.create',
    'carriere.interims.agences.index',
    'carriere.interims.prets.index',
    'carriere.interims.prets.create',
    'organisation.postes.index',
]);

it('n\'affiche aucune référence aux textes sur la fiche d\'un CDD', function () {
    $contrat = Contrat::factory()->signe()->create(['type_contrat' => TypeContrat::CDD->value]);
    $html = $this->get(route('contrats.contrats.show', $contrat))->assertOk()->getContent();
    $texte = strip_tags(preg_replace('#<(script|style)\b[^>]*>.*?</\1>#si', '', $html));

    expect($texte)->toContain('CDD — motif de recours et terme');
    expect(preg_match('/\bart\.\s*\d|CCIT|Code du travail/iu', $texte))->toBe(0);
});

it('charge la mise en forme des tableaux sans défilement horizontal', function () {
    $this->get(route('carriere.interims.index'))
        ->assertOk()
        ->assertSee('table-empilable', false)
        ->assertSee('mystyle.css', false);

    expect(file_get_contents(public_path('app/assets/css/mystyle.css')))
        ->toContain('white-space: normal')
        ->toContain('.table.table-empilable');
});
