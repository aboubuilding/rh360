<?php

namespace App\Domain\Conges\Actions;

use App\Domain\Conges\Enums\StatutDossierMaternite;
use App\Domain\Conges\Models\DossierMaternite;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class EnregistrerDossierMaternite
{
    public function executer(array $donnees, ?UploadedFile $certificat = null): DossierMaternite
    {
        return DB::transaction(function () use ($donnees, $certificat) {
            $donnees['entreprise_id'] = auth()->user()->entreprise_id;
            $donnees['statut'] = $donnees['statut'] ?? StatutDossierMaternite::DECLAREE->value;
            $donnees['cree_par'] = auth()->id();
            $donnees['etat'] = 1;

            if ($certificat) {
                $donnees['chemin_certificat_medical'] = $certificat->store('conges/maternite', 'local');
            }

            return DossierMaternite::create($donnees);
        });
    }
}