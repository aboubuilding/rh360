<?php

namespace App\Domain\Pilotage\Services;

use App\Domain\Pilotage\DTO\Indicateur;
use App\Domain\Personnel\Enums\StatutDossier;
use App\Domain\Personnel\Models\Salarie;
use Illuminate\Support\Collection;

class CalculateurIndicateursPersonnel
{
    public function calculer(int $entrepriseId): Collection
    {
        $effectifActif = Salarie::where('entreprise_id', $entrepriseId)
            ->where('actif', true)
            ->where('statut_emploi', 'Actif')
            ->count();

        $entreesMois = Salarie::where('entreprise_id', $entrepriseId)
            ->whereBetween('date_embauche', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();

        $sortiesMois = Salarie::where('entreprise_id', $entrepriseId)
            ->where('actif', false)
            ->whereBetween('updated_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();

        $dossiersIncomplets = Salarie::where('entreprise_id', $entrepriseId)
            ->where('actif', true)
            ->where('statut_dossier', StatutDossier::INCOMPLET->value)
            ->count();

        return collect([
            new Indicateur(
                cle: 'effectif_actif',
                libelle: 'Effectif actif',
                valeur: $effectifActif,
                couleur: 'primary',
                icone: 'fa-users',
                lien: route('personnel.salaries.index', ['situation' => 'actifs']),
            ),
            new Indicateur(
                cle: 'entrees_mois',
                libelle: 'Entrées du mois',
                valeur: $entreesMois,
                couleur: 'success',
                icone: 'fa-sign-in-alt',
                lien: route('personnel.salaries.index', ['du' => now()->startOfMonth()->format('Y-m-d')]),
            ),
            new Indicateur(
                cle: 'sorties_mois',
                libelle: 'Sorties du mois',
                valeur: $sortiesMois,
                couleur: 'warning',
                icone: 'fa-sign-out-alt',
            ),
            new Indicateur(
                cle: 'dossiers_incomplets',
                libelle: 'Dossiers incomplets',
                valeur: $dossiersIncomplets,
                couleur: $dossiersIncomplets > 0 ? 'warning' : 'success',
                icone: 'fa-exclamation-triangle',
                lien: route('personnel.salaries.index', ['completude' => 'incomplets']),
            ),
        ]);
    }
}