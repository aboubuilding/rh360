<?php

namespace App\Domain\Contrats\Actions;

use App\Domain\Contrats\Enums\ObjetPieceContrat;
use App\Domain\Contrats\Models\Contrat;
use App\Domain\Contrats\Models\PieceContrat;
use App\Domain\Contrats\Services\GestionnairePieces;
use Illuminate\Http\UploadedFile;

class AjouterPieceContrat
{
    public function __construct(private GestionnairePieces $gestionnaire) {}

    public function executer(
        Contrat $contrat,
        UploadedFile $fichier,
        ObjetPieceContrat $objet,
        ?string $libelle = null,
    ): PieceContrat {
        return $this->gestionnaire->enregistrer($contrat, $fichier, $objet, $libelle);
    }
}