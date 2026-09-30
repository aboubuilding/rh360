<?php

use App\Domain\Shared\Enums\Etat;

it('retourne les bons libellés', function () {
    expect(Etat::ACTIF->libelle())->toBe('Actif');
    expect(Etat::INACTIF->libelle())->toBe('Inactif');
    expect(Etat::SUPPRIME->libelle())->toBe('Supprimé');
});

it('retourne les bonnes valeurs numériques', function () {
    expect(Etat::ACTIF->value)->toBe(1);
    expect(Etat::INACTIF->value)->toBe(0);
    expect(Etat::SUPPRIME->value)->toBe(-1);
});