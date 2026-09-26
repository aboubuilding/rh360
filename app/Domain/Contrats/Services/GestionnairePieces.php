<?php

namespace App\Domain\Contrats\Services;

use App\Domain\Contrats\Enums\ObjetPieceContrat;
use App\Domain\Contrats\Models\Contrat;
use App\Domain\Contrats\Models\PieceContrat;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class GestionnairePieces
{
    private const TAILLE_MAX = 8388608; // 8 Mo
    private const MIMES_AUTORISES = ['application/pdf', 'image/png', 'image/jpeg'];

    /**
     * Enregistre une pièce jointe avec empreinte SHA-256 et stockage privé.
     */
    public function enregistrer(
        Contrat $contrat,
        UploadedFile $fichier,
        ObjetPieceContrat $objet,
        ?string $libelle = null,
    ): PieceContrat {
        $this->verifierFichier($fichier);

        $chemin = $fichier->store('contrats/pieces', 'local');
        $cheminComplet = Storage::disk('local')->path($chemin);

        $empreinte = hash_file('sha256', $cheminComplet);

        return PieceContrat::create([
            'entreprise_id' => $contrat->entreprise_id,
            'contrat_id' => $contrat->id,
            'objet' => $objet->value,
            'libelle' => $libelle ?? $objet->libelle(),
            'chemin' => $chemin,
            'type_mime' => $fichier->getMimeType(),
            'empreinte_sha256' => $empreinte,
            'cree_par' => auth()->id() ?? 1,
        ]);
    }

    public function supprimer(PieceContrat $piece): void
    {
        if ($piece->chemin && Storage::disk('local')->exists($piece->chemin)) {
            Storage::disk('local')->delete($piece->chemin);
        }
        $piece->delete();
    }

    public function telecharger(PieceContrat $piece)
    {
        if (! Storage::disk('local')->exists($piece->chemin)) {
            abort(404, 'Fichier introuvable.');
        }

        // Tracé de la consultation
        app(\App\Domain\Administration\Services\ServiceAudit::class)
            ->tracer('piece_contrat.consultee', $piece);

        return Storage::disk('local')->response($piece->chemin);
    }

    private function verifierFichier(UploadedFile $fichier): void
    {
        if ($fichier->getSize() > self::TAILLE_MAX) {
            throw new \DomainException('Le fichier dépasse 8 Mo.');
        }

        if (! in_array($fichier->getMimeType(), self::MIMES_AUTORISES, true)) {
            throw new \DomainException('Format non autorisé (PDF, PNG, JPEG uniquement).');
        }
    }
}