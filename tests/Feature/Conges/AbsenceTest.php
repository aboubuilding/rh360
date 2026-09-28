<?php

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Conges\Enums\QualificationAbsence;
use App\Domain\Conges\Enums\StatutTransmissionPaie;
use App\Domain\Conges\Models\Absence;
use App\Domain\Conges\Models\TypeConge;
use App\Domain\Personnel\Models\Salarie;

beforeEach(function () {
    $this->seed();
    $this->admin = Utilisateur::where('role', Utilisateur::ROLE_SUPER_ADMIN)->first();
    $this->actingAs($this->admin);
    $this->salarie = Salarie::first();
    $this->typeAbsence = TypeConge::where('code', 'ABS-NJ')->first();
});

it('liste les absences', function () {
    $this->get(route('conges.absences.index'))
        ->assertOk()
        ->assertSee('Absences');
});

it('constate une absence', function () {
    $this->post(route('conges.absences.store'), [
        'salarie_id' => $this->salarie->id,
        'type_conge_id' => $this->typeAbsence->id,
        'debut_le' => now()->format('Y-m-d\TH:i'),
        'fin_le' => now()->addHours(8)->format('Y-m-d\TH:i'),
        'motif' => 'Absence constatée',
    ])->assertRedirect();

    $this->assertDatabaseHas('absences', [
        'salarie_id' => $this->salarie->id,
        'qualification' => QualificationAbsence::EN_ATTENTE->value,
        'statut_transmission_paie' => StatutTransmissionPaie::A_PREPARER->value,
        'etat' => 1,
    ]);
});

it('qualifie une absence', function () {
    $absence = Absence::factory()->create([
        'entreprise_id' => 1,
        'salarie_id' => $this->salarie->id,
        'type_conge_id' => $this->typeAbsence->id,
    ]);

    $this->post(route('conges.absences.qualifier', $absence), [
        'qualification' => QualificationAbsence::INJUSTIFIEE->value,
        'decision' => 'Aucun justificatif fourni',
    ])->assertRedirect();

    expect($absence->fresh()->qualification)->toBe(QualificationAbsence::INJUSTIFIEE);
});

it('régularise une absence avec décision motivée', function () {
    $absence = Absence::factory()->create([
        'entreprise_id' => 1,
        'salarie_id' => $this->salarie->id,
        'type_conge_id' => $this->typeAbsence->id,
    ]);

    $this->post(route('conges.absences.regulariser', $absence), [
        'qualification' => QualificationAbsence::JUSTIFIEE->value,
        'decision' => 'Justificatif fourni après coup',
    ])->assertRedirect();

    expect($absence->fresh()->qualification)->toBe(QualificationAbsence::JUSTIFIEE);
    expect($absence->fresh()->decision_regularisation)->toContain('Justificatif');
});

it('transmet les absences à la paie', function () {
    $absences = Absence::factory()->count(3)->create([
        'entreprise_id' => 1,
        'salarie_id' => $this->salarie->id,
        'type_conge_id' => $this->typeAbsence->id,
    ]);

    $this->post(route('conges.absences.transmettre-paie'), [
        'absence_ids' => $absences->pluck('id')->toArray(),
        'periode_paie' => now()->format('Y-m'),
    ])->assertRedirect();

    foreach ($absences as $absence) {
        expect($absence->fresh()->statut_transmission_paie)->toBe(StatutTransmissionPaie::TRANSMIS);
        expect($absence->fresh()->periode_paie)->toBe(now()->format('Y-m'));
    }
});

it('rejette une transmission sans absence', function () {
    $this->post(route('conges.absences.transmettre-paie'), [
        'absence_ids' => [],
        'periode_paie' => now()->format('Y-m'),
    ])->assertSessionHasErrors('absence_ids');
});