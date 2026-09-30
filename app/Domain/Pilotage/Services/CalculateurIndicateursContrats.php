<?php

namespace App\Domain\Pilotage\Services;

use App\Domain\Contrats\Enums\StatutContrat;
use App\Domain\Contrats\Models\Contrat;
use App\Domain\Pilotage\DTO\Indicateur;
use Illuminate\Support\Collection;

class CalculateurIndicateursContrats
{
    /** Horizon des échéances contractuelles affichées au tableau de bord. */
    public const HORIZON_ECHEANCES_JOURS = 90;

    public function calculer(int $entrepriseId): Collection
    {
        $aValider = Contrat::where('entreprise_id', $entrepriseId)
            ->where('statut', StatutContrat::SOUMIS->value)
            ->count();

        $brouillons = Contrat::where('entreprise_id', $entrepriseId)
            ->where('statut', StatutContrat::BROUILLON->value)
            ->count();

        $echeances = Contrat::where('entreprise_id', $entrepriseId)
            ->where('statut', StatutContrat::SIGNE->value)
            ->whereNotNull('date_fin')
            ->whereBetween('date_fin', [
                now()->startOfDay(),
                now()->addDays(self::HORIZON_ECHEANCES_JOURS)->endOfDay(),
            ])
            ->count();

        return collect([
            new Indicateur(
                cle: 'contrats_a_valider',
                libelle: 'Contrats à valider',
                valeur: $aValider,
                couleur: $aValider > 0 ? 'warning' : 'success',
                icone: 'fa-file-signature',
                lien: route('contrats.contrats.index', ['statut' => StatutContrat::SOUMIS->value]),
            ),
            new Indicateur(
                cle: 'contrats_brouillons',
                libelle: 'Contrats en brouillon',
                valeur: $brouillons,
                couleur: 'secondary',
                icone: 'fa-file-alt',
                lien: route('contrats.contrats.index', ['statut' => StatutContrat::BROUILLON->value]),
            ),
            new Indicateur(
                cle: 'echeances_90j',
                libelle: 'Échéances sous 90 jours',
                valeur: $echeances,
                couleur: $echeances > 0 ? 'danger' : 'success',
                icone: 'fa-hourglass-half',
                lien: route('contrats.alertes.index'),
            ),
        ]);
    }
}
