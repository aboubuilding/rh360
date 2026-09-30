<?php

/*
| CDC §4 3.3 — Avancements : date d'éligibilité = date de référence + délai de la
| règle d'évolution ; préparation automatique d'une proposition si l'échéance est
| à moins de 90 jours, le dossier complet et aucun avancement déjà en cours.
*/

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Carriere\Enums\StatutMouvement;
use App\Domain\Carriere\Enums\TypeMouvement;
use App\Domain\Carriere\Models\MouvementCarriere;
use App\Domain\Carriere\Models\SituationCarriere;
use App\Domain\Carriere\Services\CalculateurEligibilite;
use App\Domain\Classification\Models\PositionClassification;
use App\Domain\Personnel\Models\Salarie;

beforeEach(function () {
    $this->seed();
    $this->admin = Utilisateur::where('role', Utilisateur::ROLE_SUPER_ADMIN)->first();
    $this->actingAs($this->admin);

    // Position de départ ayant un échelon suivant ; règle seedée : 24 mois minimum
    $this->position = PositionClassification::whereNotNull('position_suivante_id')->orderBy('ordre')->first();
});

function salarieAvecReference(\Carbon\CarbonInterface $reference, array $etat = []): Salarie
{
    $salarie = Salarie::factory()->create($etat);
    SituationCarriere::factory()->create([
        'salarie_id' => $salarie->id,
        'position_classification_id' => test()->position->id,
        'date_reference_avancement' => $reference,
        'date_effet_echelon' => $reference,
    ]);

    return $salarie;
}

it('liste les échéances d\'avancement', function () {
    $this->get(route('carriere.avancements.index'))
        ->assertOk()
        ->assertSee('Avancement');
});

it('calcule la date d\'éligibilité à partir de la règle d\'évolution', function () {
    $salarie = salarieAvecReference(now()->subMonths(23)->startOfDay());

    $calcul = app(CalculateurEligibilite::class)->calculer($salarie);

    expect($calcul)->not->toBeNull();
    expect($calcul['delai_mois'])->toBe(24);
    expect($calcul['date_eligibilite'])->toBe(now()->subMonths(23)->startOfDay()->addMonths(24)->format('Y-m-d'));
    expect($calcul['position_suivante_id'])->toBe($this->position->position_suivante_id);
    expect($calcul['est_eligible'])->toBeFalse();
    expect($calcul['echeance_proche'])->toBeTrue();
});

it('déclare éligible un salarié dont l\'échéance est passée', function () {
    $salarie = salarieAvecReference(now()->subMonths(30));

    expect(app(CalculateurEligibilite::class)->estEligible($salarie))->toBeTrue();
});

it('ne calcule rien sans position suivante (sommet de grille)', function () {
    $sommet = PositionClassification::whereNull('position_suivante_id')->first();
    $salarie = Salarie::factory()->create();
    SituationCarriere::factory()->create([
        'salarie_id' => $salarie->id,
        'position_classification_id' => $sommet->id,
        'date_reference_avancement' => now()->subYears(5),
    ]);

    expect(app(CalculateurEligibilite::class)->calculer($salarie))->toBeNull();
});

it('prépare une proposition pour une échéance proche et un dossier complet', function () {
    $salarie = salarieAvecReference(now()->subMonths(23));

    $this->post(route('carriere.avancements.preparer'), ['jours' => 90])->assertRedirect();

    $proposition = MouvementCarriere::where('salarie_id', $salarie->id)->sole();
    expect($proposition->type_mouvement)->toBe(TypeMouvement::AVANCEMENT);
    expect($proposition->statut)->toBe(StatutMouvement::BROUILLON);
    expect($proposition->position_classification_depart_id)->toBe($this->position->id);
    expect($proposition->position_classification_cible_id)->toBe($this->position->position_suivante_id);
});

it('ne prépare pas de proposition pour un dossier incomplet', function () {
    $salarie = salarieAvecReference(now()->subMonths(23), ['statut_dossier' => 'incomplete']);

    $this->post(route('carriere.avancements.preparer'), ['jours' => 90])->assertRedirect();

    expect(MouvementCarriere::where('salarie_id', $salarie->id)->exists())->toBeFalse();
});

it('ne prépare pas de proposition pour une échéance lointaine', function () {
    $salarie = salarieAvecReference(now()->subMonths(6));

    $this->post(route('carriere.avancements.preparer'), ['jours' => 90])->assertRedirect();

    expect(MouvementCarriere::where('salarie_id', $salarie->id)->exists())->toBeFalse();
});

it('ne duplique pas un avancement déjà en cours', function () {
    $salarie = salarieAvecReference(now()->subMonths(23));
    MouvementCarriere::factory()->create([
        'entreprise_id' => 1,
        'salarie_id' => $salarie->id,
        'type_mouvement' => TypeMouvement::AVANCEMENT->value,
        'statut' => StatutMouvement::PROPOSE->value,
    ]);

    $this->post(route('carriere.avancements.preparer'), ['jours' => 90])->assertRedirect();
    $this->post(route('carriere.avancements.preparer'), ['jours' => 90])->assertRedirect();

    expect(MouvementCarriere::where('salarie_id', $salarie->id)->count())->toBe(1);
});
