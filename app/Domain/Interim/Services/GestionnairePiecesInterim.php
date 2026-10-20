<?php

namespace App\Domain\Interim\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Stockage privé des pièces jointes des décisions d'intérim, avec empreinte SHA-256.
 */
class GestionnairePiecesInterim
{
    /**
     * @return array{chemin_piece:string, type_mime_piece:?string, empreinte_sha256:string}
     */
    public function enregistrer(UploadedFile $fichier, string $dossier): array
    {
        $chemin = $fichier->store("interims/{$dossier}", 'local');

        return [
            'chemin_piece' => $chemin,
            'type_mime_piece' => $fichier->getMimeType(),
            'empreinte_sha256' => hash_file('sha256', Storage::disk('local')->path($chemin)),
        ];
    }
}
