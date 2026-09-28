<?php

namespace App\Domain\Sst\Actions;

use App\Domain\Sst\Enums\NaturePieceSst;
use App\Domain\Sst\Models\PieceJointeSst;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class AjouterPieceJointeSst
{
    private const TAILLE_MAX = 8388608; // 8 Mo
    private const MIMES_AUTORISES = ['application/pdf', 'image/png', 'image/jpeg'];

    public function executer(
        int $entrepriseId,
        NaturePieceSst $nature,
        int $ficheId,
        UploadedFile $fichier,
        string $libelle,
    ): PieceJointeSst {
        $this->verifierFichier($fichier);

        $chemin = $fichier->store('sst/pieces', 'local');

        return PieceJointeSst::create([
            'entreprise_id' => $entrepriseId,
            'nature' => $nature->value,
            'fiche_id' => $ficheId,
            'cle_soumission' => (string) Str::uuid(),
            'libelle' => $libelle,
            'chemin' => $chemin,
            'type_mime' => $fichier->getMimeType(),
            'auteur_id' => auth()->id() ?? 1,
        ]);
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