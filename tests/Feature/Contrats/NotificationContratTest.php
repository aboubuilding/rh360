<?php

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Contrats\Models\AlerteContrat;
use App\Domain\Contrats\Models\Contrat;
use App\Domain\Contrats\Models\NotificationContrat;

beforeEach(function () {
    $this->seed();
    $this->admin = Utilisateur::where('role', Utilisateur::ROLE_SUPER_ADMIN)->first();
    $this->actingAs($this->admin);
    $this->contrat = Contrat::first();
});

it('liste les notifications de l\'utilisateur', function () {
    $this->get(route('contrats.notifications.index'))->assertOk();
});

it('marque une notification comme lue', function () {
    $alerte = AlerteContrat::create([
        'entreprise_id' => 1,
        'contrat_id' => $this->contrat->id,
        'cle' => 'test_alerte_' . uniqid(),
        'intitule' => 'Test alerte',
        'date_echeance' => now()->addDays(15),
        'base_calcul' => 'date_fin',
        'en_cours' => true,
        'etat' => 1,
    ]);

    $notification = NotificationContrat::create([
        'entreprise_id' => 1,
        'alerte_id' => $alerte->id,
        'utilisateur_id' => $this->admin->id,
        'base_calcul' => 'date_fin',
        'seuil' => 15,
    ]);

    expect($notification->lu_le)->toBeNull();

    $this->post(route('contrats.notifications.lue', $notification))
        ->assertRedirect();

    expect($notification->fresh()->lu_le)->not->toBeNull();
});

it('refuse de marquer la notification d\'un autre utilisateur', function () {
    $autre = Utilisateur::create([
        'entreprise_id' => 1,
        'nom_complet' => 'Autre',
        'identifiant' => 'autre_test',
        'password' => bcrypt('x'),
        'role' => Utilisateur::ROLE_RH,
        'actif' => true,
        'etat' => 1,
    ]);

    $alerte = AlerteContrat::create([
        'entreprise_id' => 1,
        'contrat_id' => $this->contrat->id,
        'cle' => 'test_alerte_' . uniqid(),
        'intitule' => 'Test',
        'date_echeance' => now()->addDays(15),
        'base_calcul' => 'date_fin',
        'en_cours' => true,
        'etat' => 1,
    ]);

    $notification = NotificationContrat::create([
        'entreprise_id' => 1,
        'alerte_id' => $alerte->id,
        'utilisateur_id' => $autre->id,
        'base_calcul' => 'date_fin',
        'seuil' => 15,
    ]);

    $this->post(route('contrats.notifications.lue', $notification))
        ->assertForbidden();
});