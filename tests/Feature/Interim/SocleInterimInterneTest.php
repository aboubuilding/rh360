<?php

/*
| Lot 1 — socle du module A (intérim interne) : schéma, modèles, enums, paramètres.
*/

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Classification\Models\CategorieClassification;
use App\Domain\Interim\Enums\MotifInterim;
use App\Domain\Interim\Enums\StatutInterim;
use App\Domain\Interim\Enums\TypeDecisionInterim;
use App\Domain\Interim\Models\DecisionInterim;
use App\Domain\Interim\Models\InterimInterne;
use App\Domain\Interim\Services\CalculateurDelaisInterim;
use App\Domain\Interim\Services\ParametresInterim;
use App\Domain\Organisation\Models\Poste;
use App\Domain\Personnel\Models\Salarie;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    $this->seed();
    $this->actingAs(Utilisateur::where('role', Utilisateur::ROLE_SUPER_ADMIN)->first());
});

it('crée les tables du module A et les colonnes additives des postes', function () {
    expect(Schema::hasTable('interims_internes'))->toBeTrue();
    expect(Schema::hasTable('decisions_interims'))->toBeTrue();
    expect(Schema::hasTable('avantages_interims'))->toBeTrue();
    expect(Schema::hasColumns('postes', ['categorie', 'categorie_classification_id', 'travaux_dangereux']))->toBeTrue();
});

it('conserve la catégorie texte des postes existants et les déclare non dangereux par défaut', function () {
    $poste = Poste::first();

    expect($poste->categorie)->not->toBeNull();
    expect($poste->categorie_classification_id)->toBeNull();
    expect($poste->travaux_dangereux)->toBeFalse();
});

// RG-INT-01
it('relie un intérim interne au salarié, au poste occupé, à sa catégorie et au titulaire', function () {
    $salarie = Salarie::first();
    $titulaire = Salarie::where('id', '!=', $salarie->id)->first();
    $categorie = CategorieClassification::orderByDesc('ordre')->first();
    $poste = Poste::first();
    $poste->update(['categorie_classification_id' => $categorie->id]);

    $interim = InterimInterne::factory()->motif(MotifInterim::CONGE_ANNUEL)->create([
        'salarie_id' => $salarie->id,
        'poste_occupe_id' => $poste->id,
        'categorie_occupee_id' => $categorie->id,
        'titulaire_id' => $titulaire->id,
    ])->fresh();

    expect($interim->salarie->is($salarie))->toBeTrue();
    expect($interim->posteOccupe->is($poste))->toBeTrue();
    expect($interim->categorieOccupee->is($categorie))->toBeTrue();
    expect($interim->titulaire->is($titulaire))->toBeTrue();
    expect($interim->posteOccupe->categorieClassification->is($categorie))->toBeTrue();
    expect($interim->motif)->toBe(MotifInterim::CONGE_ANNUEL);
    expect($interim->statut)->toBe(StatutInterim::EN_COURS);
});

// RG-INT-04
it('ne rend éligibles à prolongation que les motifs prévus par la CCIT', function (MotifInterim $motif, bool $eligible) {
    expect($motif->estEligibleProlongation())->toBe($eligible);
})->with([
    'maladie ou accident' => [MotifInterim::MALADIE_ACCIDENT, true],
    'congé annuel' => [MotifInterim::CONGE_ANNUEL, true],
    'congé de maternité' => [MotifInterim::CONGE_MATERNITE, true],
    'détention préventive' => [MotifInterim::DETENTION_PREVENTIVE, true],
    'AT / MP' => [MotifInterim::AT_MP, true],
    'poste vacant' => [MotifInterim::POSTE_VACANT, false],
    'réorganisation' => [MotifInterim::REORGANISATION, false],
    'autre' => [MotifInterim::AUTRE, false],
]);

// RG-INT-05
it('associe chaque motif éligible au délai légal prévu par les textes', function () {
    $delais = app(CalculateurDelaisInterim::class);

    foreach (MotifInterim::cases() as $motif) {
        expect($delais->delaiLegal($motif) !== null)->toBe($motif->estEligibleProlongation());
    }

    expect($delais->delaiLegal(MotifInterim::MALADIE_ACCIDENT))->toBe(6);
    expect($delais->delaiLegal(MotifInterim::DETENTION_PREVENTIVE))->toBe(6);
    expect($delais->delaiLegal(MotifInterim::CONGE_ANNUEL))->toBe('fin_conge');
    expect($delais->delaiLegal(MotifInterim::CONGE_MATERNITE))->toBe('fin_conge');
    expect($delais->delaiLegal(MotifInterim::AT_MP))->toBe('date_consolidation');
});

it('expose par défaut les valeurs des textes et les choix des points non tranchés', function () {
    $parametres = app(ParametresInterim::class);

    expect($parametres->get('duree_max_interim_mois'))->toBe(2);
    expect($parametres->get('delai_maladie_mois'))->toBe(6);
    expect($parametres->get('delai_detention_preventive_mois'))->toBe(6);
    expect($parametres->get('origine_delai_legal'))->toBe('debut_absence_titulaire');
    expect($parametres->get('mode_calcul_duree'))->toBe('date_a_date');
    expect($parametres->get('prorata_indemnite'))->toBe('jours_reels_du_mois');
    expect($parametres->get('cumul_interims_meme_poste'))->toBeTrue();
    expect($parametres->get('bloquer_cloture_paie_si_a_regulariser'))->toBeTrue();
});

it('historise une décision RH avec son auteur, sa date et sa pièce', function () {
    $interim = InterimInterne::factory()->create(['salarie_id' => Salarie::first()->id]);

    $interim->decisions()->create([
        'entreprise_id' => 1,
        'type_decision' => TypeDecisionInterim::PROLONGATION->value,
        'donnees' => ['ancienne_fin' => '2026-11-19', 'nouvelle_fin' => '2027-01-19'],
        'chemin_piece' => 'interims/1/certificat.pdf',
        'decide_par' => auth()->id(),
        'decide_le' => now(),
    ]);

    $decision = DecisionInterim::sole();
    expect($decision->objet->is($interim))->toBeTrue();
    expect($decision->type_decision)->toBe(TypeDecisionInterim::PROLONGATION);
    expect($decision->decide_par)->toBe(auth()->id());
    expect($decision->chemin_piece)->toBe('interims/1/certificat.pdf');
});

it('ne retient que les intérims non clos dans le scope en vigueur', function () {
    $salarie = Salarie::first();
    $enCours = InterimInterne::factory()->create(['salarie_id' => $salarie->id]);
    $aRegulariser = InterimInterne::factory()->aRegulariser()->create(['salarie_id' => $salarie->id]);
    InterimInterne::factory()->create(['salarie_id' => $salarie->id, 'statut' => StatutInterim::CLOS->value]);

    expect(InterimInterne::enVigueur()->pluck('id')->all())
        ->toEqualCanonicalizing([$enCours->id, $aRegulariser->id]);
});
