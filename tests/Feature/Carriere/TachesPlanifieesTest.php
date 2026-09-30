<?php

/*
| CDC §6 — Application à la date d'effet : une tâche planifiée quotidienne
| (Scheduler Laravel) bascule les actes programmés et recalcule les alertes.
*/

use App\Domain\Carriere\Enums\StatutMouvement;
use App\Domain\Carriere\Models\MouvementCarriere;
use Illuminate\Console\Scheduling\Schedule;

beforeEach(function () {
    $this->seed();
});

it('planifie chaque jour la bascule des actes et le recalcul des alertes', function (string $commande, string $cron) {
    $evenement = collect(app(Schedule::class)->events())
        ->first(fn ($e) => str_contains((string) $e->command, $commande));

    expect($evenement)->not->toBeNull();
    expect($evenement->expression)->toBe($cron);
})->with([
    'actes de carrière' => ['rh:appliquer-mouvements-carriere', '30 5 * * *'],
    'alertes contrats' => ['rh:balayer-alertes-contrats', '0 6 * * *'],
]);

it('applique hors session les actes programmés dont la date d\'effet est atteinte', function () {
    // Exécution console : aucun utilisateur connecté
    $salarieId = \App\Domain\Personnel\Models\Salarie::withoutGlobalScopes()->value('id');
    $echu = MouvementCarriere::factory()->create([
        'entreprise_id' => 1,
        'salarie_id' => $salarieId,
        'statut' => StatutMouvement::PROGRAMME->value,
        'date_effet' => now()->subDay(),
    ]);
    $futur = MouvementCarriere::factory()->create([
        'entreprise_id' => 1,
        'salarie_id' => $salarieId,
        'statut' => StatutMouvement::PROGRAMME->value,
        'date_effet' => now()->addWeek(),
    ]);

    $this->artisan('rh:appliquer-mouvements-carriere')->assertSuccessful();

    expect(MouvementCarriere::withoutGlobalScopes()->find($echu->id)->statut)->toBe(StatutMouvement::EFFECTIF);
    expect(MouvementCarriere::withoutGlobalScopes()->find($futur->id)->statut)->toBe(StatutMouvement::PROGRAMME);
});

it('recalcule les alertes contractuelles sans erreur', function () {
    $this->artisan('rh:balayer-alertes-contrats')->assertSuccessful();
});
