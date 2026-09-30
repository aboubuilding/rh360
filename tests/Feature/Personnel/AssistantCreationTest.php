<?php

/*
| CDC §4 2.2 — Assistant de création en cinq étapes : brouillon sauvegardé à
| chaque étape (démarrer, reprendre, abandonner) ; la validation génère
| l'affectation initiale, la situation de carrière initiale et l'acte d'entrée.
*/

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Carriere\Enums\StatutMouvement;
use App\Domain\Carriere\Models\MouvementCarriere;
use App\Domain\Carriere\Models\SituationCarriere;
use App\Domain\Classification\Models\PositionClassification;
use App\Domain\Organisation\Models\Poste;
use App\Domain\Personnel\Models\Affectation;
use App\Domain\Personnel\Models\BrouillonSalarie;
use App\Domain\Personnel\Models\Salarie;

beforeEach(function () {
    $this->seed();
    $this->rh = Utilisateur::factory()->role(Utilisateur::ROLE_RH)->create();
    $this->actingAs($this->rh);
});

function parcourirEtapes(array $surcharges = []): void
{
    $poste = Poste::first();
    $etapes = [
        1 => ['nom' => 'ASSISTANT', 'prenoms' => 'Test', 'sexe' => 'F'],
        2 => ['telephone_principal' => '90000001', 'ville' => 'Lomé'],
        3 => ['situation_matrimoniale' => 'Célibataire'],
        4 => ['numero_cnss' => 'CNSS-ASSIST'],
        5 => [
            'date_embauche' => '2026-01-05',
            'date_prise_service' => '2026-01-12',
            'structure_id' => $poste->structure_id,
            'poste_id' => $poste->id,
            'position_classification_id' => PositionClassification::orderBy('ordre')->value('id'),
        ],
    ];

    foreach ($etapes as $n => $donnees) {
        test()->post(route('personnel.salaries.wizard.etape.store', $n), array_merge($donnees, $surcharges[$n] ?? []))
            ->assertRedirect();
    }
}

it('affiche chaque étape de l\'assistant', function () {
    $this->get(route('personnel.salaries.wizard.demarrer'))->assertRedirect(route('personnel.salaries.wizard.etape', 1));
    $this->get(route('personnel.salaries.wizard.etape', 1))->assertOk()->assertSee('Identité');
});

it('sauvegarde le brouillon en base à chaque étape', function () {
    $this->get(route('personnel.salaries.wizard.demarrer'));
    $this->post(route('personnel.salaries.wizard.etape.store', 1), ['nom' => 'BROUILLON', 'prenoms' => 'Persistant'])
        ->assertRedirect(route('personnel.salaries.wizard.etape', 2));

    $brouillon = BrouillonSalarie::where('utilisateur_id', $this->rh->id)->sole();
    expect($brouillon->donnees['nom'])->toBe('BROUILLON');
    expect($brouillon->etape_courante)->toBe(2);
});

it('reprend le brouillon à l\'étape atteinte, même après déconnexion', function () {
    $this->get(route('personnel.salaries.wizard.demarrer'));
    $this->post(route('personnel.salaries.wizard.etape.store', 1), ['nom' => 'REPRISE', 'prenoms' => 'Test']);
    $this->post(route('personnel.salaries.wizard.etape.store', 2), ['ville' => 'Kara']);

    // Nouvelle session : le brouillon est en base, pas en session
    $this->flushSession();
    $this->actingAs($this->rh->fresh());

    $this->get(route('personnel.salaries.wizard.demarrer'))
        ->assertRedirect(route('personnel.salaries.wizard.etape', 3));
    $this->get(route('personnel.salaries.wizard.etape', 1))->assertOk()->assertSee('REPRISE');
});

it('interdit de sauter une étape non atteinte', function () {
    $this->get(route('personnel.salaries.wizard.demarrer'));

    $this->get(route('personnel.salaries.wizard.etape', 4))
        ->assertRedirect(route('personnel.salaries.wizard.etape', 1));
});

it('refuse de valider un brouillon incomplet', function () {
    $this->get(route('personnel.salaries.wizard.demarrer'));
    $this->post(route('personnel.salaries.wizard.etape.store', 1), ['nom' => 'INCOMPLET', 'prenoms' => 'Test']);

    $this->post(route('personnel.salaries.wizard.valider'))->assertRedirect();

    expect(Salarie::where('nom', 'INCOMPLET')->exists())->toBeFalse();
});

it('abandonne le brouillon sans créer de salarié', function () {
    $this->get(route('personnel.salaries.wizard.demarrer'));
    parcourirEtapes();

    $this->post(route('personnel.salaries.wizard.abandonner'))->assertRedirect(route('personnel.salaries.index'));

    expect(BrouillonSalarie::where('utilisateur_id', $this->rh->id)->exists())->toBeFalse();
    expect(Salarie::where('nom', 'ASSISTANT')->exists())->toBeFalse();
});

it('sépare les formulaires « Abandonner » et « Valider » dans le récapitulatif', function () {
    $this->get(route('personnel.salaries.wizard.demarrer'));
    parcourirEtapes();

    $html = $this->get(route('personnel.salaries.wizard.recapitulatif'))->assertOk()->getContent();

    // Aucun formulaire imbriqué : chaque bouton poste vers sa propre action
    expect(substr_count($html, 'wizard/valider') + substr_count($html, 'creer/valider'))->toBeGreaterThan(0);
    preg_match_all('/<form\b/i', $html, $ouvertures, PREG_OFFSET_CAPTURE);
    preg_match_all('/<\/form>/i', $html, $fermetures, PREG_OFFSET_CAPTURE);
    $profondeur = 0;
    $evenements = collect($ouvertures[0])->map(fn ($o) => [$o[1], 1])
        ->merge(collect($fermetures[0])->map(fn ($f) => [$f[1], -1]))->sortBy(0);
    foreach ($evenements as [, $delta]) {
        $profondeur += $delta;
        expect($profondeur)->toBeLessThanOrEqual(1);
    }
});

it('crée le salarié avec affectation, situation de carrière et acte d\'entrée', function () {
    $this->get(route('personnel.salaries.wizard.demarrer'));
    parcourirEtapes();

    $this->post(route('personnel.salaries.wizard.valider'))->assertRedirect();

    $salarie = Salarie::where('nom', 'ASSISTANT')->sole();
    $poste = Poste::first();

    $affectation = Affectation::where('salarie_id', $salarie->id)->where('en_cours', true)->sole();
    expect($affectation->poste_id)->toBe($poste->id);
    expect($affectation->date_debut->format('Y-m-d'))->toBe('2026-01-12');

    $situation = SituationCarriere::where('salarie_id', $salarie->id)->sole();
    expect($situation->position_classification_id)->toBe(PositionClassification::orderBy('ordre')->value('id'));
    expect($situation->date_reference_avancement->format('Y-m-d'))->toBe('2026-01-12');

    $entree = MouvementCarriere::where('salarie_id', $salarie->id)->sole();
    expect($entree->statut)->toBe(StatutMouvement::EFFECTIF);
    expect($entree->motif)->toBe('Entrée en fonction');

    // Le brouillon est consommé
    expect(BrouillonSalarie::where('utilisateur_id', $this->rh->id)->exists())->toBeFalse();
});

it('ignore les données bancaires saisies sans permission dans l\'assistant', function () {
    $this->get(route('personnel.salaries.wizard.demarrer'));
    parcourirEtapes([4 => ['compte_bancaire' => 'RIB-INTERDIT']]);
    $this->post(route('personnel.salaries.wizard.valider'));

    expect(Salarie::where('nom', 'ASSISTANT')->sole()->compte_bancaire)->toBeNull();
});
