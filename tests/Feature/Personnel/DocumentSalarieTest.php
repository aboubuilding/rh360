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

/**
 * Crée un document réel sur le disque privé pour les tests d'accès.
 */
function documentStocke(Salarie $salarie, array $attributs = []): \App\Domain\Personnel\Models\DocumentSalarie
{
    Storage::disk('local')->put('salaries/documents/test.pdf', '%PDF-1.4 test');

    return $salarie->documents()->create(array_merge([
        'type_document' => 'Pièce d\'identité',
        'chemin_fichier' => 'salaries/documents/test.pdf',
        'date_document' => '2025-01-01',
        'date_expiration' => '2030-01-01',
        'actif' => true,
        'etat' => 1,
    ], $attributs));
}

it('stocke le fichier sur le disque privé, jamais dans l\'espace public', function () {
    Storage::fake('public');

    $this->post(route('personnel.salaries.documents.store', $this->salarie), [
        'type_document' => 'Diplôme',
        'fichier' => UploadedFile::fake()->create('diplome.pdf', 100, 'application/pdf'),
    ])->assertRedirect();

    $document = $this->salarie->documents()->first();
    Storage::disk('local')->assertExists($document->chemin_fichier);
    expect(Storage::disk('public')->allFiles())->toBe([]);
});

it('sert le document via l\'accès protégé et trace la consultation', function () {
    $document = documentStocke($this->salarie);

    $this->get(route('personnel.salaries.documents.voir', [$this->salarie, $document]))->assertOk();

    expect(\App\Domain\Administration\Models\JournalAudit::where('action', 'document.consulte')
        ->where('entite_id', $document->id)->exists())->toBeTrue();
});

it('refuse l\'ouverture d\'un document sans permission', function () {
    $document = documentStocke($this->salarie);
    $auditeur = Utilisateur::factory()->role(Utilisateur::ROLE_AUDITEUR)->create();

    $this->actingAs($auditeur)
        ->get(route('personnel.salaries.documents.voir', [$this->salarie, $document]))
        ->assertForbidden();
});

it('refuse d\'ouvrir le document d\'un salarié via l\'URL d\'un autre', function () {
    $autre = Salarie::create(['entreprise_id' => 1, 'matricule' => 'TEST-DOC-2', 'nom' => 'Autre', 'prenoms' => 'X', 'etat' => 1]);
    $document = documentStocke($autre);

    $this->get(route('personnel.salaries.documents.voir', [$this->salarie, $document]))->assertNotFound();
});

it('exige un motif pour archiver un document', function () {
    $document = documentStocke($this->salarie);

    $this->post(route('personnel.salaries.documents.archiver', [$this->salarie, $document]), [])
        ->assertSessionHasErrors('motif');
    expect($document->fresh()->actif)->toBeTrue();

    $this->post(route('personnel.salaries.documents.archiver', [$this->salarie, $document]), [
        'motif' => 'Document périmé remplacé',
    ])->assertRedirect();

    $archive = $document->fresh();
    expect($archive->actif)->toBeFalse();
    expect($archive->motif_archivage)->toBe('Document périmé remplacé');
    expect($archive->archive_le)->not->toBeNull();
});

it('renouvelle un document en chaînant le nouveau à l\'ancien', function () {
    $ancien = documentStocke($this->salarie);

    $this->post(route('personnel.salaries.documents.renouveler', [$this->salarie, $ancien]), [
        'fichier' => UploadedFile::fake()->create('nouvelle-piece.pdf', 100, 'application/pdf'),
        'date_document' => '2026-01-01',
        'date_expiration' => '2031-01-01',
    ])->assertRedirect();

    expect($ancien->fresh()->actif)->toBeFalse();
    $nouveau = $this->salarie->documents()->where('actif', true)->sole();
    expect($nouveau->remplace_document_id)->toBe($ancien->id);
    expect($nouveau->type_document)->toBe($ancien->type_document);
});