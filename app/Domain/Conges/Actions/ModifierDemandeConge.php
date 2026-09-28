<?php

namespace App\Domain\Conges\Actions;

use App\Domain\Conges\Models\DemandeConge;
use App\Domain\Conges\Services\CalculateurDureeConge;
use Illuminate\Support\Facades\DB;

class ModifierDemandeConge
{
    public function __construct(private CalculateurDureeConge $calculateurDuree) {}

    public function executer(DemandeConge $demande, array $donnees): DemandeConge
    {
        if (! $demande->statut->estModifiable()) {
            throw new \DomainException('Cette demande ne peut plus être modifiée.');
        }

        return DB::transaction(function () use ($demande, $donnees) {
            // Recalculer la durée si les dates changent
            if (isset($donnees['date_debut']) || isset($donnees['date_reprise'])) {
                $type = $demande->typeConge;
                $debut = \Carbon\Carbon::parse($donnees['date_debut'] ?? $demande->date_debut);
                $reprise = \Carbon\Carbon::parse($donnees['date_reprise'] ?? $demande->date_reprise);
                $donnees['duree_jours'] = $this->calculateurDuree->calculer($debut, $reprise, $type);
            }

            $demande->update($donnees);

            return $demande->fresh();
        });
    }
}