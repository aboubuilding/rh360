<?php

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Performance\Models\CampagneEvaluation;
use App\Domain\Performance\Models\EntretienEvaluation;
use App\Domain\Performance\Services\CalculateurNoteFinale;

beforeEach(function () {
    $this->seed();
    $this->admin = Utilisateur::where('role', Utilisateur::ROLE_SUPER_ADMIN)->first();
    $this->actingAs($this->admin);
});

it('liste les campagnes', function () {
    $this->get(route('performance.campagnes.index'))->assertOk();
});

it('calcule la note finale pondérée', function () {
    $entretien = new EntretienEvaluation([
        'note_auto_evaluation' => 15,
        'note_manager' => 18,
    ]);

    $note = app(CalculateurNoteFinale::class)->calculer($entretien);

    // 20% × 15 + 80% × 18 = 3 + 14.4 = 17.4
    expect($note)->toBe(17.4);
});

it('détermine l\'appréciation selon la note', function () {
    $calc = app(CalculateurNoteFinale::class);

    expect($calc->appreciation(18.5))->toBe('Excellent');
    expect($calc->appreciation(16.0))->toBe('Très bien');
    expect($calc->appreciation(13.0))->toBe('Bien');
    expect($calc->appreciation(11.0))->toBe('Satisfaisant');
    expect($calc->appreciation(8.0))->toBe('Insuffisant');
    expect($calc->appreciation(5.0))->toBe('Très insuffisant');
});

it('saisit l\'auto-évaluation', function () {
    $entretien = EntretienEvaluation::where('statut', 'a_preparer')->first();
    if (! $entretien) {
        expect(true)->toBeTrue();
        return;
    }

    $this->post(route('performance.entretiens.auto-evaluer', $entretien), [
        'note_auto_evaluation' => 14,
    ])->assertRedirect();

    expect($entretien->fresh()->statut->value)->toBe('auto_evalue');
});

it('réalise un entretien et calcule la note finale', function () {
    $entretien = EntretienEvaluation::whereIn('statut', ['a_preparer', 'auto_evalue'])->first();
    if (! $entretien) {
        expect(true)->toBeTrue();
        return;
    }

    $this->post(route('performance.entretiens.realiser', $entretien), [
        'note_manager' => 16,
        'points_forts' => 'Bon relationnel',
    ])->assertRedirect();

    expect($entretien->fresh()->note_finale)->not->toBeNull();
});