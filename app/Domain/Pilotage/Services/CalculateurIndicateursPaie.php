<?php

namespace App\Domain\Pilotage\Services;

use App\Domain\Paie\Enums\StatutPeriode;
use App\Domain\Paie\Models\PeriodePaie;
use App\Domain\Pilotage\DTO\Indicateur;
use Illuminate\Support\Collection;

class CalculateurIndicateursPaie
{
    public function calculer(int $entrepriseId): Collection
    {
        $dernierePeriode = PeriodePaie::where('entreprise_id', $entrepriseId)
            ->recentes()
            ->first();

        if (! $dernierePeriode) {
            return collect([
                new Indicateur(
                    cle: 'paie_statut',
                    libelle: 'Dernière période de paie',
                    valeur: 'Aucune',
                    couleur: 'secondary',
                    icone: 'fa-money-check-alt',
                ),
            ]);
        }

        $aValider = $dernierePeriode->statut === StatutPeriode::CALCULEE;
        $estValidee = $dernierePeriode->statut === StatutPeriode::VALIDEE;
        $nombreBulletins = $dernierePeriode->bulletins()->count();

        return collect([
            new Indicateur(
                cle: 'paie_libelle',
                libelle: 'Dernière période de paie',
                valeur: $dernierePeriode->libelle,
                couleur: $dernierePeriode->statut->couleur(),
                icone: 'fa-money-check-alt',
                lien: route('paie.periodes.show', $dernierePeriode),
            ),
            new Indicateur(
                cle: 'paie_bulletins',
                libelle: 'Bulletins calculés',
                valeur: $nombreBulletins,
                couleur: 'primary',
                icone: 'fa-file-invoice-dollar',
                lien: route('paie.periodes.show', $dernierePeriode),
            ),
            new Indicateur(
                cle: 'paie_a_valider',
                libelle: 'Paie à valider',
                valeur: $aValider ? 'Oui' : 'Non',
                couleur: $aValider ? 'warning' : ($estValidee ? 'success' : 'secondary'),
                icone: 'fa-lock',
                lien: route('paie.periodes.show', $dernierePeriode),
            ),
        ]);
    }
}