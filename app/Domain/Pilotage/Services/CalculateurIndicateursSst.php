<?php

namespace App\Domain\Pilotage\Services;

use App\Domain\Pilotage\DTO\Indicateur;
use App\Domain\Sst\Enums\StatutVisiteMedicale;
use App\Domain\Sst\Models\ActionSecurite;
use App\Domain\Sst\Models\EvenementSecurite;
use App\Domain\Sst\Models\VisiteMedicale;
use Illuminate\Support\Collection;

class CalculateurIndicateursSst
{
    public function calculer(int $entrepriseId): Collection
    {
        // Visites médicales échues
        $visitesEchues = VisiteMedicale::where('entreprise_id', $entrepriseId)
            ->where('statut', StatutVisiteMedicale::PLANIFIEE->value)
            ->where('date_prevue', '<', now())
            ->count();

        // Visites à programmer sous 30 jours
        $visitesProches = VisiteMedicale::where('entreprise_id', $entrepriseId)
            ->where('statut', StatutVisiteMedicale::PLANIFIEE->value)
            ->whereBetween('date_prevue', [now(), now()->addDays(30)])
            ->count();

        // Actions SST en retard
        $actionsRetard = ActionSecurite::where('entreprise_id', $entrepriseId)
            ->where('statut', '!=', 'done')
            ->where('date_echeance', '<', now())
            ->count();

        // Événements sécurité en cours
        $evenementsEnCours = EvenementSecurite::where('entreprise_id', $entrepriseId)
            ->whereIn('statut', ['declared', 'in_progress'])
            ->count();

        return collect([
            new Indicateur(
                cle: 'visites_echues',
                libelle: 'Visites médicales échues',
                valeur: $visitesEchues,
                couleur: $visitesEchues > 0 ? 'danger' : 'success',
                icone: 'fa-stethoscope',
                lien: route('sst.visites.index', ['echeance' => 'echues']),
            ),
            new Indicateur(
                cle: 'visites_proches',
                libelle: 'Visites à programmer (30 j)',
                valeur: $visitesProches,
                couleur: 'info',
                icone: 'fa-calendar-plus',
                lien: route('sst.visites.index', ['echeance' => 'proches']),
            ),
            new Indicateur(
                cle: 'actions_sst_retard',
                libelle: 'Actions SST en retard',
                valeur: $actionsRetard,
                couleur: $actionsRetard > 0 ? 'danger' : 'success',
                icone: 'fa-exclamation-triangle',
                lien: route('sst.evenements.index'),
            ),
            new Indicateur(
                cle: 'evenements_en_cours',
                libelle: 'Événements SST en cours',
                valeur: $evenementsEnCours,
                couleur: 'warning',
                icone: 'fa-ambulance',
                lien: route('sst.evenements.index', ['en_cours' => '1']),
            ),
        ]);
    }
}