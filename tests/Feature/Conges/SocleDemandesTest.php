<?php

/*
| Demandes et autorisations — socle : catalogue des types de demande et des événements de
| permission (CCIT art. 6, 14, 15, 50, 51, 59 ; Code art. 69, 190, 191), paramètres des points
| d'interprétation, calendrier des jours fériés et jours ouvrés.
*/

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Conges\Enums\BasePlafond;
use App\Domain\Conges\Enums\FamilleDemande;
use App\Domain\Conges\Enums\ImpactCongeAnnuel;
use App\Domain\Conges\Enums\PeriodePlafond;
use App\Domain\Conges\Models\EvenementPermission;
use App\Domain\Conges\Models\JourFerie;
use App\Domain\Conges\Models\ParametreDemande;
use App\Domain\Conges\Models\TypeConge;
use App\Domain\Conges\Services\CalendrierJoursFeries;
use App\Domain\Conges\Services\InstallateurCatalogueDemandes;
use App\Domain\Conges\Services\ParametresDemandes;
use Carbon\Carbon;

beforeEach(function () {
    $this->seed();
    $this->actingAs(Utilisateur::where('role', Utilisateur::ROLE_SUPER_ADMIN)->first());
    $this->type = fn (string $code) => TypeConge::where('code', $code)->sole();
});

// --- Catalogue des types et des événements ---

it('installe les types de demande tirés des textes avec leurs règles', function () {
    $enfantMalade = ($this->type)('ENF-MAL');
    expect($enfantMalade->estDeDroit())->toBeTrue();
    expect([(float) $enfantMalade->plafond_valeur, $enfantMalade->plafond_unite, $enfantMalade->plafond_periode, $enfantMalade->plafond_base])
        ->toBe([10.0, 'jour', PeriodePlafond::ANNEE_CIVILE, BasePlafond::ENFANT]);

    $assemblee = ($this->type)('SYN-ASSEMB');
    expect([$assemblee->delai_depot, $assemblee->delai_depot_unite, $assemblee->anciennete_min_mois])->toBe([3, 'ouvres', 6]);
    // L'ancienneté de 6 mois n'est exigée que pour les assemblées statutaires.
    expect(($this->type)('SYN-COMM')->anciennete_min_mois)->toBeNull();

    // Les séminaires comptent pour l'acquisition du congé, les commissions non.
    expect(($this->type)('SYN-SEMIN')->impact_conge_annuel)->toBe(ImpactCongeAnnuel::AUCUNE);
    expect(($this->type)('SYN-COMM')->impact_conge_annuel)->toBe(ImpactCongeAnnuel::SUSPEND);

    $credit = ($this->type)('SYN-CREDIT');
    expect([(float) $credit->plafond_valeur, $credit->plafond_periode, $credit->prise_continue_autorisee, $credit->report_credit_autorise])
        ->toBe([30.0, PeriodePlafond::MOIS, false, false]);
    expect((float) ($this->type)('DELEG')->plafond_valeur)->toBe(15.0);

    $maladie = ($this->type)('MAL');
    expect([$maladie->delai_information_heures, $maladie->delai_justification_jours, $maladie->delai_justification_depart])->toBe([72, 6, 'debut_absence']);
    // Les couches et la maladie (dans la limite de 6 mois) comptent pour l'acquisition du congé.
    expect(($this->type)('MAT')->impact_conge_annuel)->toBe(ImpactCongeAnnuel::AUCUNE);
    expect($maladie->impact_conge_annuel)->toBe(ImpactCongeAnnuel::AUCUNE);

    $sansSolde = ($this->type)('SANS-SOLDE');
    expect([$sansSolde->estDeDroit(), $sansSolde->anciennete_min_mois])->toBe([false, 60]);
    expect(($this->type)('CA')->estDeDroit())->toBeFalse();

    // Les anciens types qui recouvraient les permissions ne sont plus installés.
    expect(TypeConge::whereIn('code', ['DECES', 'MARIAGE', 'PAT', 'PERM-EXC'])->exists())->toBeFalse();
    expect(TypeConge::whereNull('famille')->exists())->toBeFalse();
});

it('installe les durées des permissions exceptionnelles par événement', function () {
    $jours = EvenementPermission::orderBy('ordre')->get()->mapWithKeys(fn ($e) => [$e->code => (float) $e->jours])->all();

    expect($jours)->toBe([
        'DECES-CONJOINT' => 4.0, 'DECES-ASCENDANT' => 4.0, 'DECES-DESCENDANT' => 4.0, 'DECES-BEAU-PARENT' => 3.0,
        'DECES-FRERE-SOEUR' => 2.0, 'MARIAGE-SALARIE' => 3.0, 'MARIAGE-PROCHE' => 1.0, 'NAISSANCE' => 2.0,
        'BAPTEME' => 1.0, 'DEMENAGEMENT' => 2.0,
    ]);
    expect(EvenementPermission::where('code', 'NAISSANCE')->value('delai_prise_mois'))->toBe(6);

    $permission = ($this->type)('PERM-EVT');
    expect([$permission->delai_justification_jours, $permission->delai_justification_depart, $permission->estDeDroit()])->toBe([8, 'evenement', true]);
    expect($permission->evenements)->toHaveCount(10);
});

it('ne remplace pas les réglages des RH sans l\'option d\'alignement', function () {
    $installateur = app(InstallateurCatalogueDemandes::class);
    ($this->type)('ENF-MAL')->update(['plafond_valeur' => 12]);
    EvenementPermission::where('code', 'NAISSANCE')->update(['jours' => 3]);
    TypeConge::create(['entreprise_id' => 1, 'code' => 'FORMATION', 'nom' => 'Formation', 'categorie' => 'absence', 'unite' => 'jour_calendaire']);

    $resultat = $installateur->installer(1);
    expect([$resultat['crees'], $resultat['evenements'], $resultat['a_verifier']])->toBe([0, 0, ['FORMATION']]);
    expect((float) ($this->type)('ENF-MAL')->plafond_valeur)->toBe(12.0);
    expect((float) EvenementPermission::where('code', 'NAISSANCE')->value('jours'))->toBe(3.0);

    $installateur->installer(1, aligner: true);
    expect((float) ($this->type)('ENF-MAL')->plafond_valeur)->toBe(10.0);
    expect((float) EvenementPermission::where('code', 'NAISSANCE')->value('jours'))->toBe(2.0);
});

it('complète un type existant sans règles et crée les types manquants', function () {
    TypeConge::where('code', 'SYN-CONGRES')->forceDelete();
    ($this->type)('MAL')->update(['famille' => null, 'delai_information_heures' => null, 'delai_justification_jours' => 5]);

    $this->artisan('demandes:installer-catalogue', ['--entreprise' => 1])->assertSuccessful();

    expect(($this->type)('SYN-CONGRES')->plafond_valeur)->not->toBeNull();
    $maladie = ($this->type)('MAL');
    expect([$maladie->famille, $maladie->delai_information_heures])->toBe([FamilleDemande::MALADIE, 72]);
    // Le délai saisi par les RH n'est remplacé qu'avec l'option d'alignement.
    expect($maladie->delai_justification_jours)->toBe(5);
});

// --- Types et événements à l'écran ---

it('affiche les règles des types sans référence aux textes', function () {
    $page = $this->get(route('conges.types.index'))->assertOk()
        ->assertSee('De droit')->assertSee('Soumis à accord')
        ->assertSee('10 jours par année civile, par enfant')
        ->assertSee('Permissions exceptionnelles par événement')
        ->assertSee('Naissance au foyer');

    expect($page->getContent())->not->toMatch('/\bart\.\s*\d/i')->not->toContain('CCIT');
});

it('valide les règles d\'un type : un plafond a une unité et une période', function () {
    $type = ($this->type)('ENF-MAL');
    $donnees = [
        'code' => 'ENF-MAL', 'nom' => $type->nom, 'categorie' => $type->categorie->value, 'unite' => $type->unite->value,
        'traitement_salarial' => 'maintien', 'impact_conge_annuel' => 'aucune', 'impact_anciennete' => 'maintenue',
        'famille' => 'famille', 'de_droit' => '1', 'plafond_valeur' => 15, 'plafond_unite' => '', 'plafond_periode' => '',
    ];

    $this->putJson(route('conges.types.update', $type), $donnees)->assertStatus(422)->assertJsonValidationErrors(['plafond_unite', 'plafond_periode']);

    $this->putJson(route('conges.types.update', $type), ['plafond_unite' => 'jour', 'plafond_periode' => 'annee_service', 'plafond_base' => 'salarie'] + $donnees)->assertOk();
    expect(($this->type)('ENF-MAL')->plafond_periode)->toBe(PeriodePlafond::ANNEE_SERVICE);
});

it('permet d\'allonger une permission mais pas de la réduire sous la durée des textes', function () {
    $frere = EvenementPermission::where('code', 'DECES-FRERE-SOEUR')->sole();

    $this->putJson(route('conges.evenements.update', $frere), ['jours' => 1, 'actif' => 1])->assertStatus(422);
    expect((float) $frere->fresh()->jours)->toBe(2.0);

    $this->putJson(route('conges.evenements.update', $frere), ['jours' => 3, 'piece_attendue' => 'Acte de décès', 'actif' => 1])->assertOk();
    expect((float) $frere->fresh()->jours)->toBe(3.0);
});

// --- Paramètres ---

it('applique par défaut la lecture la plus favorable des points d\'interprétation', function () {
    $parametres = app(ParametresDemandes::class);

    expect($parametres->get('permissions_plafond_annuel'))->toBeNull();
    expect($parametres->get('conge_decompte_feries'))->toBeFalse();
    expect($parametres->get('maternite_maintien_integral'))->toBeTrue();
    expect($parametres->get('veuvage_cumul_permission'))->toBeTrue();
    expect($parametres->get('maladie_notification_tardive'))->toBe('depuis_notification');
    expect($parametres->get('conge_report_max_annees'))->toBe(2);
});

it('enregistre et rétablit les paramètres depuis l\'écran, sans référence aux textes', function () {
    $page = $this->get(route('conges.parametres'))->assertOk()->assertSee('Permissions exceptionnelles')->assertSee('Par défaut');
    expect($page->getContent())->not->toMatch('/\bart\.\s*\d/i');

    $valeurs = collect(app(ParametresDemandes::class)->definitions())
        ->map(fn ($d, $cle) => app(ParametresDemandes::class)->defaut($cle))
        ->map(fn ($v) => is_bool($v) ? (int) $v : $v)
        ->all();

    $this->put(route('conges.parametres.update'), ['permissions_plafond_annuel' => 10, 'conge_decompte_feries' => 1] + $valeurs)
        ->assertRedirect(route('conges.parametres'))->assertSessionHasNoErrors();

    app()->forgetScopedInstances();
    expect(app(ParametresDemandes::class)->get('permissions_plafond_annuel'))->toBe(10);
    expect(ParametreDemande::find(1)->valeurs)->toBe(['conge_decompte_feries' => true, 'permissions_plafond_annuel' => 10]);

    $this->post(route('conges.parametres.reinitialiser'))->assertRedirect();
    app()->forgetScopedInstances();
    expect(app(ParametresDemandes::class)->get('permissions_plafond_annuel'))->toBeNull();
});

it('refuse un paramètre moins favorable que les textes', function () {
    $valeurs = collect(app(ParametresDemandes::class)->definitions())
        ->map(fn ($d, $cle) => app(ParametresDemandes::class)->defaut($cle))
        ->map(fn ($v) => is_bool($v) ? (int) $v : $v)
        ->all();

    $this->put(route('conges.parametres.update'), [
        'conge_delai_ordre_depart_jours' => 10,
        'hs_max_semaine' => 22,
        'maternite_semaines' => 12,
        'conge_report_max_annees' => 3,
        'hs_max_jour_urgence' => 9,
    ] + $valeurs)->assertSessionHasErrors([
        'conge_delai_ordre_depart_jours', 'hs_max_semaine', 'maternite_semaines', 'conge_report_max_annees', 'hs_max_jour_urgence',
    ]);
});

// --- Calendrier des jours fériés ---

it('propose les fêtes de l\'année, fêtes mobiles comprises, sans doublon', function () {
    expect(CalendrierJoursFeries::paques(2026)->toDateString())->toBe('2026-04-05');
    expect(CalendrierJoursFeries::paques(2027)->toDateString())->toBe('2027-03-28');

    $this->post(route('conges.jours-feries.proposer'), ['annee' => 2026])->assertRedirect();
    $dates = JourFerie::whereYear('date', 2026)->orderBy('date')->pluck('libelle', 'date')
        ->mapWithKeys(fn ($l, $d) => [Carbon::parse($d)->toDateString() => $l])->all();

    expect($dates)->toHaveCount(10);
    expect($dates['2026-04-06'])->toBe('Lundi de Pâques');
    expect($dates['2026-05-14'])->toBe('Ascension');
    expect($dates['2026-05-25'])->toBe('Lundi de Pentecôte');
    expect(JourFerie::where('origine', 'propose')->count())->toBe(10);

    expect(app(CalendrierJoursFeries::class)->proposer(2026, 1))->toBe(0);
});

it('ajoute et retire un jour férié, sans doublon de date', function () {
    $this->post(route('conges.jours-feries.store'), ['date' => '2026-03-20', 'libelle' => 'Aïd el-Fitr'])->assertRedirect()->assertSessionHasNoErrors();
    $this->post(route('conges.jours-feries.store'), ['date' => '2026-03-20', 'libelle' => 'Doublon'])->assertSessionHasErrors('date');

    $this->get(route('conges.jours-feries.index', ['annee' => 2026]))->assertOk()->assertSee('Aïd el-Fitr')->assertSee('Saisi');

    $jour = JourFerie::where('date', '2026-03-20')->sole();
    $this->delete(route('conges.jours-feries.destroy', $jour))->assertRedirect();
    expect(JourFerie::count())->toBe(0);
});

it('compte les jours ouvrés en excluant les week-ends et les jours fériés', function () {
    $calendrier = app(CalendrierJoursFeries::class);
    $calendrier->proposer(2026, 1);

    // Convocation reçue le lundi 20 avril pour une assemblée le jeudi 23 : 2 jours ouvrés entre les deux.
    expect($calendrier->joursOuvresEntre(Carbon::parse('2026-04-20'), Carbon::parse('2026-04-23'), 1))->toBe(2);
    // Du jeudi 30 avril au mardi 5 mai : le 1er mai est férié, restent le lundi 4.
    expect($calendrier->joursOuvresEntre(Carbon::parse('2026-04-30'), Carbon::parse('2026-05-05'), 1))->toBe(1);
    // 3 jours ouvrés après le vendredi 24 avril : le lundi 27 avril est férié.
    expect($calendrier->ajouterJoursOuvres(Carbon::parse('2026-04-24'), 3, 1)->toDateString())->toBe('2026-04-30');
    expect($calendrier->estOuvre(Carbon::parse('2026-04-25'), 1))->toBeFalse();

    // Entreprise travaillant le samedi.
    ParametreDemande::create(['entreprise_id' => 1, 'valeurs' => ['jours_ouvres' => 'lun_sam']]);
    app()->forgetScopedInstances();
    expect(app(CalendrierJoursFeries::class)->estOuvre(Carbon::parse('2026-04-25'), 1))->toBeTrue();
    expect(app(CalendrierJoursFeries::class)->estOuvre(Carbon::parse('2026-04-26'), 1))->toBeFalse();
});
