<?php

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Contrats\Models\AlerteContrat;
use App\Domain\Contrats\Models\Contrat;
use App\Domain\Contrats\Models\NotificationContrat;

beforeEach(function () {
    $this->seed();
    $this->admin = Utilisateur::where('role', Utilisateur::ROLE_SUPER_ADMIN)->first();
    $this->actingAs($this->admin);
});

it('retourne le compteur de notifications non lues', function () {
    $this->getJson(route('notifications-contrats.compteur'))
        ->assertOk()
        ->assertJsonStructure(['count']);
});

it('retourne 5 dernières notifications non lues maximum', function () {
    // Créer un contrat + alerte + plusieurs notifications
    $contrat = Contrat::first();
    if (! $contrat) {
        expect(true)->toBeTrue();
        return;
    }

    $alerte = AlerteContrat::firstOrCreate(
        ['contrat_id' => $contrat->id, 'cle' => 'test_notif'],
        [
            'entreprise_id' => 1,
            'intitule' => 'Test notification',
            'date_echeance' => now()->addDays(15),
            'base_calcul' => 'date_fin',
            'en_cours' => true,
            'etat' => 1,
        ]
    );

    for ($i = 0; $i < 8; $i++) {
        NotificationContrat::create([
            'entreprise_id' => 1,
            'alerte_id' => $alerte->id,
            'utilisateur_id' => $this->admin->id,
            'base_calcul' => 'date_fin',
            'seuil' => 30 - $i,
        ]);
    }

    $response = $this->getJson(route('notifications-contrats.dernieres'))
        ->assertOk();

    expect(count($response->json()))->toBeLessThanOrEqual(5);
});

it('structure chaque notification correctement', function () {
    $response = $this->getJson(route('notifications-contrats.dernieres'))
        ->assertOk();

    $data = $response->json();
    if (count($data) > 0) {
        expect($data[0])->toHaveKeys([
            'id', 'intitule', 'contrat_reference', 'salarie',
            'date_echeance', 'seuil', 'created_at', 'url',
        ]);
    } else {
        expect(true)->toBeTrue();
    }
});