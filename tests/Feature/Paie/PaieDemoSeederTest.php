<?php

/*
| Données de démonstration de la paie : onze salariés illustrant chacun un cas, calculés sur le
| mois en cours ; seul le cas 11 (salaire sous le SMIG) est volontairement bloquant.
*/

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Paie\Models\AnomaliePaie;
use App\Domain\Paie\Models\BulletinPaie;
use App\Domain\Personnel\Models\Salarie;
use Database\Seeders\PaieDemoSeeder;

beforeEach(function () {
    $this->seed();
    $this->actingAs(Utilisateur::where('role', Utilisateur::ROLE_SUPER_ADMIN)->first());
});

it('crée les onze cas de démonstration et calcule leurs bulletins', function () {
    $this->seed(PaieDemoSeeder::class);
    // Relancé, il remet les salariés de démonstration dans leur état de départ.
    $this->seed(PaieDemoSeeder::class);

    $demo = Salarie::where('matricule', 'like', 'DEMO-PAIE-%')->pluck('id', 'matricule');
    expect($demo)->toHaveCount(11);

    $bulletins = BulletinPaie::whereIn('salarie_id', $demo)->with('lignes')->get()->keyBy(fn ($b) => $b->salarie_id);
    expect($bulletins)->toHaveCount(11);
    $codes = fn (string $matricule) => $bulletins[$demo[$matricule]]->lignes->pluck('code')->all();

    expect($codes('DEMO-PAIE-01'))->toContain('PRIME-TRANSP');
    expect($codes('DEMO-PAIE-03'))->toContain('HS');
    expect($codes('DEMO-PAIE-04'))->toContain('CONGE-ALLOC');
    expect($codes('DEMO-PAIE-05'))->toContain('MAL-PLEIN');
    expect($codes('DEMO-PAIE-06'))->toContain('MAT-AVANCE');
    expect($codes('DEMO-PAIE-07'))->toContain('ABS-RET');
    expect($codes('DEMO-PAIE-08'))->toContain('SAISIE-ATT');
    expect($codes('DEMO-PAIE-09'))->toContain('COT-SYND');
    expect($codes('DEMO-PAIE-10'))->toContain('PREAVIS-IND')->toContain('ICC')->toContain('IND-LIC');

    $bloquantes = AnomaliePaie::where('gravite', 'bloquante')->whereIn('salarie_id', $demo)->pluck('salarie_id')->unique()->values()->all();
    expect($bloquantes)->toBe([$demo['DEMO-PAIE-11']]);
});
