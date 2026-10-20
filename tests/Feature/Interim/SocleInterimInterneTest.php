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
it('associe chaque motif éligible à un délai légal paramétré', function () {
    $delais = config('interim.delais_legaux_par_motif');

    foreach (MotifInterim::cases() as $motif) {
        if ($motif->estEligibleProlongation()) {
            expect($delais)->toHaveKey($motif->cleDelaiLegal());
        }
    }

    expect($delais['maladie'])->toBe(6);
    expect($delais['detention_preventive'])->toBe(6);
    expect($delais['conges'])->toBe('fin_conge');
    expect($delais['at_mp'])->toBe('date_consolidation');
});

it('expose les paramètres non tranchés par les textes avec leurs valeurs par défaut', function () {
    expect(config('interim.duree_max_interim_mois'))->toBe(2);
    expect(config('interim.mode_calcul_duree'))->toBe('date_a_date');
    expect(config('interim.prorata_indemnite'))->toBe('jours_reels_du_mois');
    expect(config('interim.cumul_interims_meme_poste'))->toBeTrue();
    expect(config('interim.bloquer_cloture_paie_si_a_regulariser'))->toBeTrue();
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
