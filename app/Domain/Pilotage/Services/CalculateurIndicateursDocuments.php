<?php

namespace App\Domain\Pilotage\Services;

use App\Domain\Personnel\Models\DocumentSalarie;
use App\Domain\Pilotage\DTO\Indicateur;
use Illuminate\Support\Collection;

class CalculateurIndicateursDocuments
{
    public function calculer(int $entrepriseId): Collection
    {
        $expires = DocumentSalarie::whereHas('salarie', fn ($q) => $q->where('entreprise_id', $entrepriseId))
            ->where('actif', true)
            ->whereNotNull('date_expiration')
            ->where('date_expiration', '<', now())
            ->count();

        $aRenouveler30j = DocumentSalarie::whereHas('salarie', fn ($q) => $q->where('entreprise_id', $entrepriseId))
            ->where('actif', true)
            ->whereNotNull('date_expiration')
            ->whereBetween('date_expiration', [now(), now()->addDays(30)])
            ->count();

        return collect([
            new Indicateur(
                cle: 'documents_expires',
                libelle: 'Documents expirés',
                valeur: $expires,
                couleur: $expires > 0 ? 'danger' : 'success',
                icone: 'fa-file-excel',
                lien: route('personnel.salaries.index'),
            ),
            new Indicateur(
                cle: 'documents_a_renouveler',
                libelle: 'À renouveler sous 30 j',
                valeur: $aRenouveler30j,
                couleur: $aRenouveler30j > 0 ? 'warning' : 'success',
                icone: 'fa-sync',
            ),
        ]);
    }
}