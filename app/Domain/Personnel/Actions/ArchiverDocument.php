<?php

namespace App\Domain\Personnel\Actions;

use App\Domain\Personnel\Models\DocumentSalarie;

class ArchiverDocument
{
    public function executer(DocumentSalarie $document, string $motif): void
    {
        $document->update([
            'actif' => false,
            'archive_le' => now(),
            'motif_archivage' => $motif,
        ]);
    }
}