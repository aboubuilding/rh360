<?php

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Contrats\Enums\ObjetPieceContrat;
use App\Domain\Contrats\Models\Contrat;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed();
    Storage::fake('local');
    $this->admin = Utilisateur::where('role', Utilisateur::ROLE_SUPER_ADMIN)->first();
    $this->actingAs($this->admin);
    $this->contrat = Contrat::first();
});

it('ajoute une pièce jointe avec empreinte SHA-256', function () {
    $fichier = UploadedFile::fake()->create('contrat.pdf', 500, 'application/pdf');

    $this->post(route('contrats.contrats.pieces.store', $this->contrat), [
        'objet' => ObjetPieceContrat::CONTRAT_SIGNE->value,
        'libelle' => 'Contrat signé original',
        'fichier' => $fichier,
    ])->assertRedirect();

    expect($this->contrat->pieces()->count())->toBe(1);
    $piece = $this->contrat->pieces()->first();
    expect($piece->empreinte_sha256)->toHaveLength(64); // SHA-256 hex = 64 caractères
});

it('refuse un fichier trop lourd', function () {
    $fichier = UploadedFile::fake()->create('gros.pdf', 20000);

    $this->post(route('contrats.contrats.pieces.store', $this->contrat), [
        'objet' => ObjetPieceContrat::AUTRE->value,
        'fichier' => $fichier,
    ])->assertSessionHasErrors('fichier');
});

it('refuse un fichier avec un mauvais type MIME', function () {
    $fichier = UploadedFile::fake()->create('script.exe', 100);

    $this->post(route('contrats.contrats.pieces.store', $this->contrat), [
        'objet' => ObjetPieceContrat::AUTRE->value,
        'fichier' => $fichier,
    ])->assertSessionHasErrors('fichier');
});