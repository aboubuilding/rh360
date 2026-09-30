<?php

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Paie\Actions\CalculerPeriode;
use App\Domain\Paie\Actions\SaisirElementVariable;
use App\Domain\Paie\Enums\StatutPeriode;
use App\Domain\Paie\Models\BulletinPaie;
use App\Domain\Paie\Models\PeriodePaie;
use App\Domain\Paie\Models\RubriquePaie;
use App\Domain\Paie\Models\SaisiePaie;
use App\Domain\Personnel\Models\Salarie;

beforeEach(function () {
    $this->seed();
    $this->admin = Utilisateur::where('role', Utilisateur::ROLE_SUPER_ADMIN)->first();
    $this->actingAs($this->admin);
    $this->periodeFigee = PeriodePaie::where('statut', StatutPeriode::VALIDEE->value)->first();
});

it('dispose d\'une période validée dans les données de démonstration', function () {
    expect($this->periodeFigee)->not->toBeNull();
    expect($this->periodeFigee->estFigee())->toBeTrue();
});

it('interdit la saisie sur une période figée', function () {
    $saisiesAvant = SaisiePaie::where('periode_id', $this->periodeFigee->id)->count();

    $this->post(route('paie.periodes.saisir', $this->periodeFigee), [
        'salarie_id' => Salarie::first()->id,
        'rubrique_id' => RubriquePaie::first()->id,
        'montant' => 50000,
    ])->assertForbidden();

    expect(SaisiePaie::where('periode_id', $this->periodeFigee->id)->count())->toBe($saisiesAvant);
});

it('bloque aussi la saisie au niveau de l\'action métier', function () {
    app(SaisirElementVariable::class)->executer(
        $this->periodeFigee, Salarie::first()->id, RubriquePaie::first()->id, 1, 0, 50000,
    );
})->throws(DomainException::class, 'figée');

it('interdit le recalcul d\'une période figée', function () {
    $bulletinsAvant = BulletinPaie::where('periode_id', $this->periodeFigee->id)
        ->orderBy('id')->get(['id', 'montant_net', 'updated_at'])->toArray();

    $this->post(route('paie.periodes.calculer', $this->periodeFigee))->assertForbidden();

    expect(BulletinPaie::where('periode_id', $this->periodeFigee->id)
        ->orderBy('id')->get(['id', 'montant_net', 'updated_at'])->toArray())->toBe($bulletinsAvant);
});

it('bloque aussi le recalcul au niveau de l\'action métier', function () {
    app(CalculerPeriode::class)->executer($this->periodeFigee);
})->throws(DomainException::class);

it('autorise le super admin à rouvrir une période', function () {
    $this->post(route('paie.periodes.reouvrir', $this->periodeFigee), [
        'motif' => 'Correction d\'une erreur de saisie identifiée après validation.',
    ])->assertRedirect();

    expect($this->periodeFigee->fresh()->statut)->toBe(StatutPeriode::CALCULEE);
});

it('refuse la réouverture sans motif', function () {
    $this->post(route('paie.periodes.reouvrir', $this->periodeFigee), [])
        ->assertSessionHasErrors('motif');

    expect($this->periodeFigee->fresh()->statut)->toBe(StatutPeriode::VALIDEE);
});

it('refuse la réouverture par un non super admin', function () {
    $this->actingAs(Utilisateur::factory()->role(Utilisateur::ROLE_DRH)->create());

    $this->post(route('paie.periodes.reouvrir', $this->periodeFigee), [
        'motif' => 'Tentative non autorisée',
    ])->assertForbidden();

    expect($this->periodeFigee->fresh()->statut)->toBe(StatutPeriode::VALIDEE);
});
