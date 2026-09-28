<?php

namespace App\Domain\Pilotage\Services;

use App\Domain\Conges\Enums\StatutDemandeConge;
use App\Domain\Conges\Models\DemandeConge;
use App\Domain\Pilotage\DTO\Indicateur;
use Illuminate\Support\Collection;

class CalculateurIndicateursConges
{
    public function calculer(int $entrepriseId): Collection
    {
        $aTraiter = DemandeConge::where('entreprise_id', $entrepriseId)
            ->where('statut', StatutDemandeConge::SOUMISE->value)
            ->count();

        $programmes = DemandeConge::where('entreprise_id', $entrepriseId)
            ->whereIn('statut', [
                StatutDemandeConge::AUTORISEE->value,
                StatutDemandeConge::PROGRAMMEE->value,
            ])
            ->where('date_debut', '>', now())
            ->count();

        $enCours = DemandeConge::where('entreprise_id', $entrepriseId)
            ->where('statut', StatutDemandeConge::EN_COURS->value)
            ->count();

        $reprisesRetard = DemandeConge::where('entreprise_id', $entrepriseId)
            ->where('statut', StatutDemandeConge::EN_COURS->value)
            ->where('date_reprise', '<', now())
            ->count();

        return collect([
            new Indicateur(
                cle: 'conges_a_traiter',
                libelle: 'Demandes à traiter',
                valeur: $aTraiter,
                couleur: $aTraiter > 0 ? 'info' : 'success',
                icone: 'fa-tasks',
                lien: route('conges.demandes.index', ['vue' => 'a_traiter']),
            ),
            new Indicateur(
                cle: 'conges_programmes',
                libelle: 'Congés programmés',
                valeur: $programmes,
                couleur: 'primary',
                icone: 'fa-calendar-check',
                lien: route('conges.demandes.index', ['vue' => 'programmes']),
            ),
            new Indicateur(
                cle: 'conges_en_cours',
                libelle: 'Congés en cours',
                valeur: $enCours,
                couleur: 'warning',
                icone: 'fa-hourglass-half',
                lien: route('conges.demandes.index', ['vue' => 'en_cours']),
            ),
            new Indicateur(
                cle: 'reprises_retard',
                libelle: 'Reprises en retard',
                valeur: $reprisesRetard,
                couleur: $reprisesRetard > 0 ? 'danger' : 'success',
                icone: 'fa-exclamation-triangle',
                lien: route('conges.demandes.index', ['vue' => 'reprises_retard']),
            ),
        ]);
    }
}