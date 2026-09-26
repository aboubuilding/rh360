<?php

namespace App\Http\Controllers\Personnel;

use App\Domain\Administration\Services\ServiceAudit;
use App\Domain\Personnel\Actions\ArchiverDocument;
use App\Domain\Personnel\Models\DocumentSalarie;
use App\Domain\Personnel\Models\Salarie;
use App\Domain\Personnel\Requests\StoreDocumentSalarieRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentSalarieController extends Controller
{
    /**
     * Ajoute un document à un salarié.
     */
    public function store(StoreDocumentSalarieRequest $request, Salarie $salarie)
    {
        $this->authorize('update', $salarie);

        $donnees = $request->validated();
        $donnees['salarie_id'] = $salarie->id;
        $donnees['chemin_fichier'] = $request->file('fichier')
            ->store('salaries/documents', 'local');
        $donnees['actif'] = true;
        $donnees['etat'] = 1;

        unset($donnees['fichier']);

        DocumentSalarie::create($donnees);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Document ajouté.']);
        }

        return back()->with('success', 'Document ajouté.');
    }

    /**
     * Télécharge un document après contrôle d'accès et trace la consultation.
     */
    public function voir(Salarie $salarie, DocumentSalarie $document)
    {
        $this->authorize('view', $document);
        $this->verifierAppartenance($salarie, $document);

        if (! Storage::disk('local')->exists($document->chemin_fichier)) {
            abort(404, 'Fichier introuvable.');
        }

        // Tracé : consultation d'un document contractuel
        app(ServiceAudit::class)->tracer('document.consulte', $document, [
            'salarie_id' => $salarie->id,
            'type_document' => $document->type_document,
        ]);

        return Storage::disk('local')->response($document->chemin_fichier);
    }

    /**
     * Renouvelle un document : archive l'ancien, crée le nouveau avec chaînage.
     */
    public function renouveler(Request $request, Salarie $salarie, DocumentSalarie $document)
    {
        $this->authorize('update', $salarie);
        $this->verifierAppartenance($salarie, $document);

        $donnees = $request->validate([
            'fichier' => ['required', 'file', 'mimes:pdf,png,jpg,jpeg', 'max:8192'],
            'date_document' => ['nullable', 'date'],
            'date_expiration' => ['nullable', 'date', 'after_or_equal:date_document'],
        ], [
            'fichier.required' => 'Le fichier est obligatoire.',
            'fichier.file' => 'Le fichier est invalide.',
            'fichier.mimes' => 'Le fichier doit être au format PDF, PNG, JPG ou JPEG.',
            'fichier.max' => 'Le fichier ne doit pas dépasser 8 Mo.',
            'date_expiration.after_or_equal' => 'La date d\'expiration doit être postérieure ou égale à la date du document.',
        ]);

        // Archiver l'ancien document
        $document->update([
            'actif' => false,
            'archive_le' => now(),
            'motif_archivage' => 'Renouvellement',
        ]);

        // Créer le nouveau document (reprend les champs non écrasés)
        $nouveau = $document->replicate(['archive_le', 'motif_archivage']);
        $nouveau->chemin_fichier = $request->file('fichier')
            ->store('salaries/documents', 'local');
        $nouveau->date_document = $donnees['date_document'] ?? now();
        $nouveau->date_expiration = $donnees['date_expiration'] ?? null;
        $nouveau->remplace_document_id = $document->id;
        $nouveau->actif = true;
        $nouveau->archive_le = null;
        $nouveau->motif_archivage = null;
        $nouveau->etat = 1;
        $nouveau->save();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Document renouvelé.']);
        }

        return back()->with('success', 'Document renouvelé.');
    }

    /**
     * Archive un document avec un motif obligatoire (conserve la trace).
     */
    public function archiver(Request $request, Salarie $salarie, DocumentSalarie $document)
    {
        $this->authorize('update', $salarie);
        $this->verifierAppartenance($salarie, $document);

        $donnees = $request->validate([
            'motif' => ['required', 'string', 'min:5', 'max:500'],
        ], [
            'motif.required' => 'Le motif d\'archivage est obligatoire.',
            'motif.min' => 'Le motif doit contenir au moins 5 caractères.',
            'motif.max' => 'Le motif ne doit pas dépasser 500 caractères.',
        ]);

        app(ArchiverDocument::class)->executer($document, $donnees['motif']);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Document archivé.']);
        }

        return back()->with('success', 'Document archivé.');
    }

    /**
     * Supprime (logiquement) un document et efface le fichier physique.
     */
    public function destroy(Salarie $salarie, DocumentSalarie $document)
    {
        $this->authorize('delete', $document);
        $this->verifierAppartenance($salarie, $document);

        if ($document->chemin_fichier) {
            Storage::disk('local')->delete($document->chemin_fichier);
        }

        $document->marquerSupprime();

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Document supprimé.']);
        }

        return back()->with('success', 'Document supprimé.');
    }

    /**
     * Vérifie que le document appartient bien au salarié de l'URL.
     * En défense en profondeur, même si scopeBindings() est actif sur la route.
     */
    private function verifierAppartenance(Salarie $salarie, DocumentSalarie $document): void
    {
        if ($document->salarie_id !== $salarie->id) {
            abort(404, 'Document introuvable pour ce salarié.');
        }
    }
}