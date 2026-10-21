<?php

/*
| Demandes et autorisations — circuit : attente de justificatif, validation automatique des
| absences de droit (qui ne se refusent pas), retour au valideur des absences soumises à accord,
| requalification à l'échéance, prolongation pour force majeure, régularisation d'une absence
| constatée et historique immuable (CCIT art. 14, 50, 51).
*/

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Conges\Actions\RefuserDemandeConge;
use App\Domain\Conges\Enums\QualificationAbsence;
use App\Domain\Conges\Enums\StatutDemandeConge;
use App\Domain\Conges\Models\Absence;
use App\Domain\Conges\Models\DemandeConge;
use App\Domain\Conges\Models\EvenementPermission;
use App\Domain\Conges\Models\HistoriqueDemande;
use App\Domain\Conges\Models\ParametreDemande;
use App\Domain\Conges\Models\SoldeConge;
use App\Domain\Conges\Models\TypeConge;
use App\Domain\Personnel\Models\Salarie;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->travelTo(now()->setDate(2026, 10, 1)->setTime(9, 0));
    $this->seed();
    Storage::fake('local');
    $this->actingAs(Utilisateur::where('role', Utilisateur::ROLE_SUPER_ADMIN)->first());
    // Les demandes de démonstration du salarié sont retirées : les dates des tests ne chevauchent rien.
    $this->salarie = Salarie::orderBy('id')->firstOrFail();
    \Illuminate\Support\Facades\DB::table('demandes_conges')->where('salarie_id', $this->salarie->id)->delete();
    $this->type = fn (string $code) => TypeConge::where('code', $code)->sole();

    // Crée une demande par l'écran, puis la soumet.
    $this->demander = function (string $code, string $debut, string $reprise, array $autres = []): DemandeConge {
        $this->post(route('conges.demandes.store'), [
            'salarie_id' => $this->salarie->id,
            'type_conge_id' => ($this->type)($code)->id,
            'date_debut' => $debut,
            'date_reprise' => $reprise,
        ] + $autres)->assertRedirect()->assertSessionHasNoErrors();

        $demande = DemandeConge::latest('id')->firstOrFail();
        $this->post(route('conges.demandes.soumettre', $demande));

        return $demande->fresh();
    };
    $this->decesFrere = fn () => [
        'evenement_permission_id' => EvenementPermission::where('code', 'DECES-FRERE-SOEUR')->value('id'),
        'date_evenement' => '2026-10-01',
    ];
    $this->deposer = fn (DemandeConge $d, bool $conforme = true) => $this->post(route('conges.demandes.pieces.store', $d), [
        'nature' => 'Acte de décès',
        'fichier' => UploadedFile::fake()->create('acte.pdf', 40, 'application/pdf'),
        'conforme' => $conforme ? 1 : 0,
    ]);
});

it('laisse au valideur une demande soumise à accord et trace chaque étape', function () {
    $demande = ($this->demander)('CA', '2026-10-20', '2026-10-30');

    expect($demande->statut)->toBe(StatutDemandeConge::SOUMISE);
    expect($demande->historique->map(fn ($h) => $h->vers_statut->value)->all())->toBe(['draft', 'submitted']);
});

it('met en attente une permission sans justificatif, puis la valide automatiquement sur pièce conforme', function () {
    $demande = ($this->demander)('PERM-EVT', '2026-10-02', '2026-10-04', ($this->decesFrere)());

    expect($demande->statut)->toBe(StatutDemandeConge::ATTENTE_JUSTIFICATIF);
    // Au plus tard 8 jours après l'événement.
    expect($demande->echeance_justificatif->toDateString())->toBe('2026-10-09');

    ($this->deposer)($demande)->assertRedirect()->assertSessionHas('success');

    $demande->refresh();
    expect($demande->statut)->toBe(StatutDemandeConge::AUTORISEE);
    expect($demande->validation_automatique)->toBeTrue();
    expect($demande->historique->last()->automatique)->toBeTrue();
    expect($demande->pieces->sole()->conforme)->toBeTrue();
});

it('garde en attente une demande dont la pièce n\'est pas conforme', function () {
    $demande = ($this->demander)('PERM-EVT', '2026-10-02', '2026-10-04', ($this->decesFrere)());
    $this->post(route('conges.demandes.pieces.store', $demande), [
        'nature' => 'Acte de décès', 'fichier' => UploadedFile::fake()->create('acte.pdf', 40, 'application/pdf'),
    ]);
    $piece = $demande->pieces()->sole();

    $this->post(route('conges.demandes.pieces.controler', [$demande, $piece]), ['conforme' => 0])->assertSessionHasErrors('motif_non_conforme');
    $this->post(route('conges.demandes.pieces.controler', [$demande, $piece]), ['conforme' => 0, 'motif_non_conforme' => 'Acte illisible'])->assertSessionHas('success');

    expect($demande->fresh()->statut)->toBe(StatutDemandeConge::ATTENTE_JUSTIFICATIF);
    expect($piece->fresh()->motif_non_conforme)->toBe('Acte illisible');
});

it('valide aussitôt une absence de droit sans justificatif requis', function () {
    $demande = ($this->demander)('DELEG', '2026-10-05', '2026-10-06', ['duree_heures' => 2]);

    expect($demande->statut)->toBe(StatutDemandeConge::AUTORISEE);
    expect($demande->validation_automatique)->toBeTrue();
});

it('ne laisse pas refuser une absence de droit', function () {
    $type = ($this->type)('ENF-MAL');
    $type->update(['justificatif_requis' => false, 'de_droit' => true]);
    $demande = DemandeConge::factory()->create([
        'entreprise_id' => 1, 'salarie_id' => $this->salarie->id, 'type_conge_id' => $type->id,
        'statut' => StatutDemandeConge::SOUMISE->value, 'date_debut' => '2026-10-05', 'date_reprise' => '2026-10-07', 'duree_jours' => 2,
    ]);

    $this->post(route('conges.demandes.refuser', $demande), ['motif' => 'Service chargé'])->assertForbidden();
    expect(fn () => app(RefuserDemandeConge::class)->executer($demande, 'Service chargé'))
        ->toThrow(DomainException::class, 'ne peut pas être refusée');
    expect($demande->fresh()->statut)->toBe(StatutDemandeConge::SOUMISE);
});

it('rend au valideur une demande soumise à accord dès que son justificatif est conforme', function () {
    ($this->type)('SANS-SOLDE')->update(['justificatif_requis' => true]);
    $demande = ($this->demander)('SANS-SOLDE', '2026-11-02', '2026-12-02');
    expect($demande->statut)->toBe(StatutDemandeConge::ATTENTE_JUSTIFICATIF);

    ($this->deposer)($demande);

    expect($demande->fresh()->statut)->toBe(StatutDemandeConge::SOUMISE);
    expect($demande->fresh()->validation_automatique)->toBeFalse();
});

it('requalifie en absence non justifiée une permission dont la pièce arrive après l\'échéance', function () {
    // Décès d'un frère le 1er octobre, absence les 2 et 3, pièce remise le 11 (J+10).
    $demande = ($this->demander)('PERM-EVT', '2026-10-02', '2026-10-04', ($this->decesFrere)());

    $this->travelTo(now()->setDate(2026, 10, 9)->setTime(23, 0));
    $this->artisan('demandes:echeances', ['--entreprise' => 1])->assertSuccessful();
    expect($demande->fresh()->statut)->toBe(StatutDemandeConge::ATTENTE_JUSTIFICATIF);

    $this->travelTo(now()->setDate(2026, 10, 10)->setTime(0, 20));
    $this->artisan('demandes:echeances', ['--entreprise' => 1])->assertSuccessful();

    $demande->refresh();
    expect([$demande->statut, $demande->requalification])->toBe([StatutDemandeConge::REQUALIFIEE, 'absence_non_justifiee']);
    $absence = $demande->absence;
    expect([$absence->typeConge->code, $absence->qualification, $absence->debut_le->toDateString(), $absence->fin_le->toDateString()])
        ->toBe(['ABS-NJ', QualificationAbsence::INJUSTIFIEE, '2026-10-02', '2026-10-03']);

    // La pièce reçue le 11 est conservée, sans remettre en cause la requalification.
    $this->travelTo(now()->setDate(2026, 10, 11)->setTime(9, 0));
    ($this->deposer)($demande);
    expect($demande->fresh()->statut)->toBe(StatutDemandeConge::REQUALIFIEE);
    expect($demande->pieces()->count())->toBe(1);
});

it('déduit du congé payé une permission non justifiée si l\'entreprise l\'a choisi', function () {
    ParametreDemande::create(['entreprise_id' => 1, 'valeurs' => ['permissions_justificatif_tardif' => 'deduction_conge']]);
    app()->forgetScopedInstances();
    $demande = ($this->demander)('PERM-EVT', '2026-10-02', '2026-10-04', ($this->decesFrere)());

    $this->travelTo(now()->setDate(2026, 10, 10));
    $this->artisan('demandes:echeances', ['--entreprise' => 1])->assertSuccessful();

    expect($demande->fresh()->requalification)->toBe('deduction_conge');
    $solde = SoldeConge::where('salarie_id', $this->salarie->id)->where('type_conge_id', ($this->type)('CA')->id)->where('annee', 2026)->sole();
    expect((float) $solde->ajustement)->toBe(-2.0);
    expect($demande->fresh()->absence_id)->toBeNull();
});

it('prolonge le délai pour force majeure, sans requalifier avant la nouvelle échéance', function () {
    $demande = ($this->demander)('PERM-EVT', '2026-10-02', '2026-10-04', ($this->decesFrere)());

    $this->postJson(route('conges.demandes.prolonger-echeance', $demande), ['echeance' => '2026-10-05', 'motif' => 'Mairie fermée'])->assertStatus(422);
    $this->postJson(route('conges.demandes.prolonger-echeance', $demande), ['echeance' => '2026-10-20', 'motif' => 'Mairie fermée pour grève'])->assertOk();

    $this->travelTo(now()->setDate(2026, 10, 15));
    $this->artisan('demandes:echeances', ['--entreprise' => 1])->assertSuccessful();

    $demande->refresh();
    expect([$demande->statut, $demande->echeance_justificatif->toDateString()])->toBe([StatutDemandeConge::ATTENTE_JUSTIFICATIF, '2026-10-20']);
    expect($demande->historique->last()->motif)->toContain('Mairie fermée pour grève');
});

it('exige une demande préalable, sauf force majeure précisée', function () {
    $this->post(route('conges.demandes.store'), [
        'salarie_id' => $this->salarie->id, 'type_conge_id' => ($this->type)('PERM-EVT')->id,
        'date_debut' => '2026-09-29', 'date_reprise' => '2026-10-01',
    ] + ($this->decesFrere)());
    $demande = DemandeConge::latest('id')->firstOrFail();

    $this->postJson(route('conges.demandes.soumettre', $demande))->assertStatus(422)->assertJson(['message' => 'Cette absence doit être demandée avant son début. S\'il s\'agit d\'un cas de force majeure, indiquez-le pour la régulariser.']);

    $this->post(route('conges.demandes.store'), [
        'salarie_id' => $this->salarie->id, 'type_conge_id' => ($this->type)('PERM-EVT')->id,
        'date_debut' => '2026-09-29', 'date_reprise' => '2026-10-01', 'force_majeure' => 1,
    ] + ($this->decesFrere)())->assertSessionHasErrors('motif_force_majeure');

    $this->post(route('conges.demandes.store'), [
        'salarie_id' => $this->salarie->id, 'type_conge_id' => ($this->type)('PERM-EVT')->id,
        'date_debut' => '2026-09-29', 'date_reprise' => '2026-10-01', 'force_majeure' => 1,
        'motif_force_majeure' => 'Décès survenu dans la nuit',
    ] + ($this->decesFrere)())->assertSessionHasNoErrors();
    $this->post(route('conges.demandes.soumettre', DemandeConge::latest('id')->first()));

    expect(DemandeConge::latest('id')->first()->statut)->toBe(StatutDemandeConge::ATTENTE_JUSTIFICATIF);
});

it('refuse un événement qui n\'appartient pas au type de demande', function () {
    $this->post(route('conges.demandes.store'), [
        'salarie_id' => $this->salarie->id, 'type_conge_id' => ($this->type)('CA')->id,
        'date_debut' => '2026-10-20', 'date_reprise' => '2026-10-30',
    ] + ($this->decesFrere)())->assertSessionHasErrors('evenement_permission_id');
});

it('régularise une absence constatée par une demande de maladie, qualifiée à la validation', function () {
    $absence = Absence::create([
        'entreprise_id' => 1, 'salarie_id' => $this->salarie->id, 'type_conge_id' => ($this->type)('ABS-NJ')->id,
        'debut_le' => '2026-09-28 00:00:00', 'fin_le' => '2026-09-30 23:59:59', 'statut' => 'constatée',
        'qualification' => QualificationAbsence::EN_ATTENTE->value, 'statut_transmission_paie' => 'a_preparer', 'etat' => 1,
    ]);

    $this->post(route('conges.absences.regulariser-par-demande', $absence), [
        'type_conge_id' => ($this->type)('MAL')->id, 'motif_force_majeure' => 'Hospitalisation en urgence',
    ])->assertRedirect()->assertSessionHasNoErrors();

    $demande = DemandeConge::where('absence_id', $absence->id)->sole();
    expect([$demande->statut, $demande->force_majeure, $demande->date_debut->toDateString(), $demande->date_reprise->toDateString()])
        ->toBe([StatutDemandeConge::ATTENTE_JUSTIFICATIF, true, '2026-09-28', '2026-10-01']);
    // Certificat dans les 6 jours à compter du premier jour d'indisponibilité.
    expect($demande->echeance_justificatif->toDateString())->toBe('2026-10-04');

    ($this->deposer)($demande);
    expect($demande->fresh()->statut)->toBe(StatutDemandeConge::AUTORISEE);
    expect($absence->fresh()->qualification)->toBe(QualificationAbsence::JUSTIFIEE);

    $this->post(route('conges.absences.regulariser-par-demande', $absence), [
        'type_conge_id' => ($this->type)('MAL')->id, 'motif_force_majeure' => 'Doublon',
    ])->assertSessionHas('error');

    $this->get(route('conges.absences.show', $absence))->assertOk()->assertSee($demande->numero_demande);
});

it('affiche les justificatifs, l\'échéance et l\'historique, sans bouton de refus pour une absence de droit', function () {
    $demande = ($this->demander)('PERM-EVT', '2026-10-02', '2026-10-04', ($this->decesFrere)());

    $this->get(route('conges.demandes.show', $demande))->assertOk()
        ->assertSee('En attente de justificatif')
        ->assertSee('09/10/2026')
        ->assertSee('Historique')
        ->assertSee('Requalifier')
        ->assertDontSee('class="btn btn-outline-danger js-refuser"', false);
});

it('rend l\'historique des demandes non modifiable', function () {
    $demande = ($this->demander)('CA', '2026-10-20', '2026-10-30');
    $ligne = $demande->historique->first();

    expect(fn () => $ligne->update(['motif' => 'modifié']))->toThrow(LogicException::class);
    expect(fn () => $ligne->delete())->toThrow(LogicException::class);
    expect(HistoriqueDemande::where('demande_id', $demande->id)->count())->toBe(2);
});
