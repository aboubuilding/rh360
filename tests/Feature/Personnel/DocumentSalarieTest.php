<?php

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Personnel\Models\Salarie;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed();
    Storage::fake('local');
    $this->actingAs(Utilisateur::where('role', Utilisateur::ROLE_SUPER_ADMIN)->first());
    $this->salarie = Salarie::create([
        'entreprise_id' => 1,
        'matricule' => 'TEST-DOC',
        'nom' => 'Doc',
        'prenoms' => 'Test',
        'etat' => 1,
    ]);
});

it('ajoute un document', function () {
    $fichier = UploadedFile::fake()->create('diplome.pdf', 500, 'application/pdf');

    $this->post(route('personnel.salaries.documents.store', $this->salarie), [
        'type_document' => 'Diplôme',
        'fichier' => $fichier,
        'date_document' => '2026-01-01',
    ])->assertRedirect();

    expect($this->salarie->documents()->count())->toBe(1);
});

it('rejette un fichier trop lourd', function () {
    $fichier = UploadedFile::fake()->create('gros.pdf', 20000);

    $this->post(route('personnel.salaries.documents.store', $this->salarie), [
        'type_document' => 'Diplôme',
        'fichier' => $fichier,
    ])->assertSessionHasErrors('fichier');
});