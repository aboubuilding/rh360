<?php

/*
| Demandes et autorisations — compteurs et contrôles bloquants à la soumission : plafonds
| (enfant malade, congrès, crédit d'heures, délégation), enfant concerné, ancienneté minimale,
| délai de dépôt en jours ouvrés, durée et délai de prise des permissions, chevauchements
| (CCIT art. 6, 15, 50, 51, 59).
*/

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Conges\Enums\StatutDemandeConge;
use App\Domain\Conges\Models\DemandeConge;
use App\Domain\Conges\Models\EvenementPermission;
use App\Domain\Conges\Models\TypeConge;
use App\Domain\Personnel\Models\MembreFoyer;
use App\Domain\Personnel\Models\Salarie;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->travelTo(now()->setDate(2026, 10, 1)->setTime(9, 0));
    $this->seed();
    $this->actingAs(Utilisateur::where('role', Utilisateur::ROLE_SUPER_ADMIN)->first());

    // Salarié sans absence ni demande antérieure, embauché il y a 10 ans.
    $this->salarie = Salarie::orderBy('id')->firstOrFail();
    DB::table('demandes_conges')->where('salarie_id', $this->salarie->id)->delete();
    DB::table('absences')->where('salarie_id', $this->salarie->id)->delete();
    $this->salarie->update(['date_embauche' => '2016-09-01']);

    $this->type = fn (string $code) => TypeConge::where('code', $code)->sole();
    $this->enfant = fn (string $naissance, array $autres = []) => MembreFoyer::create($autres + [
        'salarie_id' => $this->salarie->id, 'lien_parente' => 'Enfant', 'nom' => 'KOFFI',
        'prenoms' => 'Ama', 'date_naissance' => $naissance, 'est_a_charge' => true, 'actif' => true, 'etat' => 1,
    ]);

    // Crée et soumet une demande ; renvoie la réponse JSON de la soumission.
    $this->soumettre = function (string $code, string $debut, string $reprise, array $autres = []) {
        $this->post(route('conges.demandes.store'), [
            'salarie_id' => $this->salarie->id, 'type_conge_id' => ($this->type)($code)->id,
            'date_debut' => $debut, 'date_reprise' => $reprise,
        ] + $autres)->assertSessionHasNoErrors();

        return $this->postJson(route('conges.demandes.soumettre', DemandeConge::latest('id')->firstOrFail()));
    };
    $this->evenement = fn (string $code, string $date) => [
        'evenement_permission_id' => EvenementPermission::where('code', $code)->value('id'),
        'date_evenement' => $date,
    ];
});

// --- Enfant malade ---

it('plafonne l\'absence pour enfant malade à 10 jours par an et par enfant', function () {
    $ama = ($this->enfant)('2018-03-12');
    $kofi = ($this->enfant)('2020-06-01', ['prenoms' => 'Kofi']);

    ($this->soumettre)('ENF-MAL', '2026-10-05', '2026-10-11', ['membre_foyer_id' => $ama->id])->assertOk();
    ($this->soumettre)('ENF-MAL', '2026-10-12', '2026-10-16', ['membre_foyer_id' => $ama->id])->assertOk();
    // 11e jour pour le même enfant.
    ($this->soumettre)('ENF-MAL', '2026-10-19', '2026-10-20', ['membre_foyer_id' => $ama->id])
        ->assertStatus(422)->assertJsonFragment(['message' => 'Plafond dépassé : 10 jour(s) en 2026 pour cet enfant, 10 déjà décompté(s), 1 demandé(s).']);

    // Le plafond s'apprécie par enfant.
    ($this->soumettre)('ENF-MAL', '2026-10-21', '2026-10-28', ['membre_foyer_id' => $kofi->id])->assertOk();
});

it('n\'ouvre l\'absence pour enfant malade qu\'à un enfant à charge de 21 ans révolus au plus', function () {
    $adulte = ($this->enfant)('2004-01-01');
    ($this->soumettre)('ENF-MAL', '2026-10-05', '2026-10-07', ['membre_foyer_id' => $adulte->id])
        ->assertStatus(422)->assertJsonFragment(['message' => 'L\'enfant Ama a 22 ans : au-delà de 21 ans révolus, l\'absence n\'est pas prévue.']);

    // 21 ans et 8 mois : encore dans la limite.
    $jeune = ($this->enfant)('2005-02-01', ['prenoms' => 'Yao']);
    ($this->soumettre)('ENF-MAL', '2026-10-05', '2026-10-07', ['membre_foyer_id' => $jeune->id])->assertOk();

    $nonACharge = ($this->enfant)('2015-01-01', ['prenoms' => 'Esi', 'est_a_charge' => false]);
    ($this->soumettre)('ENF-MAL', '2026-10-12', '2026-10-13', ['membre_foyer_id' => $nonACharge->id])
        ->assertStatus(422)->assertJsonFragment(['message' => 'L\'enfant Esi n\'est pas déclaré à la charge du salarié.']);

    ($this->soumettre)('ENF-MAL', '2026-10-14', '2026-10-15')->assertStatus(422)
        ->assertJsonFragment(['message' => 'Désignez l\'enfant concerné parmi les enfants déclarés du salarié.']);
});

it('refuse un membre qui n\'appartient pas au foyer du salarié', function () {
    $autre = Salarie::where('id', '!=', $this->salarie->id)->orderBy('id')->firstOrFail();
    $etranger = MembreFoyer::create(['salarie_id' => $autre->id, 'lien_parente' => 'Enfant', 'nom' => 'X', 'prenoms' => 'Y', 'date_naissance' => '2018-01-01', 'est_a_charge' => true, 'actif' => true, 'etat' => 1]);

    $this->post(route('conges.demandes.store'), [
        'salarie_id' => $this->salarie->id, 'type_conge_id' => ($this->type)('ENF-MAL')->id,
        'date_debut' => '2026-10-05', 'date_reprise' => '2026-10-07', 'membre_foyer_id' => $etranger->id,
    ])->assertSessionHasErrors('membre_foyer_id');

    $this->getJson(route('conges.demandes.membres-foyer', $this->salarie))->assertOk()->assertJsonCount(0);
    ($this->enfant)('2018-03-12');
    $this->getJson(route('conges.demandes.membres-foyer', $this->salarie))->assertOk()->assertJsonCount(1);
});

// --- Activités syndicales ---

it('exige une convocation 3 jours ouvrés avant l\'assemblée, sauf force majeure', function () {
    // Assemblée le jeudi 8 octobre : convocation au plus tard le lundi 5.
    ($this->soumettre)('SYN-ASSEMB', '2026-10-08', '2026-10-09', ['date_demande' => '2026-10-06'])
        ->assertStatus(422)->assertJsonFragment(['message' => 'Demande déposée trop tard : elle devait l\'être 3 jour(s) ouvré(s) avant le début de l\'absence, soit au plus tard le 05/10/2026, sauf force majeure.']);

    ($this->soumettre)('SYN-ASSEMB', '2026-10-08', '2026-10-09', ['date_demande' => '2026-10-06', 'force_majeure' => 1, 'motif_force_majeure' => 'Assemblée extraordinaire convoquée en urgence'])->assertOk();
    DemandeConge::latest('id')->first()->update(['statut' => StatutDemandeConge::ANNULEE->value]);

    ($this->soumettre)('SYN-ASSEMB', '2026-10-08', '2026-10-09', ['date_demande' => '2026-10-05'])->assertOk();
});

it('exige 6 mois d\'ancienneté pour une assemblée statutaire, mais pas pour une commission paritaire', function () {
    $this->salarie->update(['date_embauche' => '2026-05-01']);

    ($this->soumettre)('SYN-ASSEMB', '2026-10-08', '2026-10-09', ['date_demande' => '2026-10-01'])
        ->assertStatus(422)->assertJsonFragment(['message' => 'Ancienneté insuffisante : 6 mois exigé(s), 5 mois à la date de début.']);

    ($this->soumettre)('SYN-COMM', '2026-10-08', '2026-10-09')->assertOk();
});

it('plafonne le congrès syndical à 10 jours par an', function () {
    ($this->soumettre)('SYN-CONGRES', '2026-10-05', '2026-10-17')
        ->assertStatus(422)->assertJsonFragment(['message' => 'Plafond dépassé : 10 jour(s) en 2026, 0 déjà décompté(s), 12 demandé(s).']);

    ($this->soumettre)('SYN-CONGRES', '2026-10-05', '2026-10-15')->assertOk();
    ($this->soumettre)('SYN-CONGRES', '2026-11-02', '2026-11-04')->assertStatus(422);
    ($this->soumettre)('SYN-CONGRES', '2027-01-11', '2027-01-13')->assertOk();
});

it('ne laisse prendre le crédit confédéral ni d\'un seul tenant ni au-delà de 30 heures par mois', function () {
    // 30 heures sur 3 jours consécutifs : 10 heures par jour.
    ($this->soumettre)('SYN-CREDIT', '2026-10-05', '2026-10-08', ['duree_heures' => 30])
        ->assertStatus(422)->assertJsonFragment(['message' => 'Ce crédit d\'heures ne se prend pas d\'un seul tenant : 8 heure(s) par jour au plus, soit 24 heure(s) sur 3 jour(s) ouvré(s).']);

    ($this->soumettre)('SYN-CREDIT', '2026-10-05', '2026-10-08', ['duree_heures' => 24])->assertOk();
    ($this->soumettre)('SYN-CREDIT', '2026-10-13', '2026-10-14', ['duree_heures' => 8])
        ->assertStatus(422)->assertJsonFragment(['message' => 'Plafond dépassé : 30 heure(s) en octobre 2026, 24 déjà décompté(s), 8 demandé(s).']);

    // Les heures non prises en octobre ne se reportent pas, mais novembre ouvre un nouveau crédit.
    ($this->soumettre)('SYN-CREDIT', '2026-11-03', '2026-11-05', ['duree_heures' => 16])->assertOk();
});

it('plafonne les heures de délégation à 15 heures par mois', function () {
    ($this->soumettre)('DELEG', '2026-10-05', '2026-10-06', ['duree_heures' => 15])->assertOk();
    ($this->soumettre)('DELEG', '2026-10-20', '2026-10-21', ['duree_heures' => 1])->assertStatus(422);
    ($this->soumettre)('DELEG', '2026-10-20', '2026-10-21')->assertStatus(422)
        ->assertJsonFragment(['message' => 'Indiquez la durée de l\'absence en heures.']);
});

// --- Congé sans solde ---

it('refuse un congé sans solde à 4 ans et 11 mois d\'ancienneté', function () {
    $this->salarie->update(['date_embauche' => '2021-12-01']);

    ($this->soumettre)('SANS-SOLDE', '2026-11-02', '2026-12-02')
        ->assertStatus(422)->assertJsonFragment(['message' => 'Ancienneté insuffisante : 5 an(s) exigé(s), 4 an(s) et 11 mois à la date de début.']);

    $this->salarie->update(['date_embauche' => '2021-11-01']);
    $demande = DemandeConge::latest('id')->first();
    $this->postJson(route('conges.demandes.soumettre', $demande))->assertOk();
    expect($demande->fresh()->statut)->toBe(StatutDemandeConge::SOUMISE);
});

// --- Permissions exceptionnelles ---

it('limite la permission à la durée prévue pour l\'événement', function () {
    ($this->soumettre)('PERM-EVT', '2026-10-02', '2026-10-05', ($this->evenement)('DECES-FRERE-SOEUR', '2026-10-01'))
        ->assertStatus(422)->assertJsonFragment(['message' => 'Durée trop longue pour « Décès d\'un frère ou d\'une sœur » : 2 jour(s) au plus.']);

    // Prise en deux fois pour le même événement : 1 + 1 jour, pas davantage.
    ($this->soumettre)('PERM-EVT', '2026-10-02', '2026-10-03', ($this->evenement)('DECES-FRERE-SOEUR', '2026-10-01'))->assertOk();
    ($this->soumettre)('PERM-EVT', '2026-10-05', '2026-10-06', ($this->evenement)('DECES-FRERE-SOEUR', '2026-10-01'))->assertOk();
    ($this->soumettre)('PERM-EVT', '2026-10-07', '2026-10-08', ($this->evenement)('DECES-FRERE-SOEUR', '2026-10-01'))
        ->assertStatus(422)->assertJsonFragment(['message' => 'Durée trop longue pour « Décès d\'un frère ou d\'une sœur » : 2 jour(s) au plus, dont 2 déjà accordé(s).']);

    ($this->soumettre)('PERM-EVT', '2026-10-12', '2026-10-13')->assertStatus(422)
        ->assertJsonFragment(['message' => 'Précisez l\'événement à l\'origine de la permission.']);
});

it('accepte une permission de naissance prise 5 mois après, pas 7 mois après', function () {
    ($this->soumettre)('PERM-EVT', '2026-10-05', '2026-10-07', ($this->evenement)('NAISSANCE', '2026-05-01'))->assertOk();

    ($this->soumettre)('PERM-EVT', '2026-10-12', '2026-10-14', ($this->evenement)('NAISSANCE', '2026-03-01'))
        ->assertStatus(422)->assertJsonFragment(['message' => 'Permission à prendre dans les 6 mois suivant l\'événement, soit au plus tard le 01/09/2026.']);
});

// --- Chevauchements ---

it('refuse deux absences qui se chevauchent, sauf deux demandes en heures', function () {
    ($this->soumettre)('CA', '2026-10-19', '2026-10-31')->assertOk();
    $numero = DemandeConge::latest('id')->value('numero_demande');

    ($this->soumettre)('SYN-COMM', '2026-10-23', '2026-10-24')
        ->assertStatus(422)->assertJsonFragment(['message' => "Ces dates chevauchent la demande {$numero} (Congé annuel payé, du 19/10/2026 au 30/10/2026)."]);
    // Des heures de délégation pendant le congé non plus.
    ($this->soumettre)('DELEG', '2026-10-22', '2026-10-23', ['duree_heures' => 2])->assertStatus(422);

    // Délégation et crédit syndical le même jour : possible.
    ($this->soumettre)('DELEG', '2026-10-06', '2026-10-07', ['duree_heures' => 2])->assertOk();
    ($this->soumettre)('SYN-CREDIT', '2026-10-06', '2026-10-07', ['duree_heures' => 3])->assertOk();

    // Une demande annulée libère les dates.
    DemandeConge::where('numero_demande', $numero)->update(['statut' => StatutDemandeConge::ANNULEE->value]);
    ($this->soumettre)('SYN-COMM', '2026-10-23', '2026-10-24')->assertOk();
});

it('affiche le compteur sur la demande', function () {
    $ama = ($this->enfant)('2018-03-12');
    ($this->soumettre)('ENF-MAL', '2026-10-05', '2026-10-08', ['membre_foyer_id' => $ama->id])->assertOk();

    $this->get(route('conges.demandes.show', DemandeConge::latest('id')->first()))->assertOk()
        ->assertSee('10 jour(s) en 2026 pour Ama')
        ->assertSee('cette demande : 3 ; reste après : 7', false);
});
