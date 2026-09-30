<?php

namespace App\Domain\Conges\Actions;

use App\Domain\Conges\Enums\StatutDemandeConge;
use App\Domain\Conges\Models\DemandeConge;
use App\Domain\Conges\Services\CircuitDemandeConge;
use Illuminate\Support\Facades\DB;

class DemarrerConge
{
    public function __construct(private CircuitDemandeConge $circuit) {}

    /**
     * Constate le départ en congé (statut « en cours »), à partir de la date de départ.
     * Une demande autorisée mais pas encore programmée est programmée au passage.
     */
    public function executer(DemandeConge $demande): DemandeConge
    {
        if (! $demande->date_debut || $demande->date_debut->isFuture()) {
            throw new \DomainException(
                'Le congé ne peut démarrer qu\'à partir de sa date de départ ('
                .($demande->date_debut?->format('d/m/Y') ?? 'non renseignée').').'
            );
        }

        return DB::transaction(function () use ($demande) {
            if ($demande->statut === StatutDemandeConge::AUTORISEE) {
                $demande = $this->circuit->transitionner($demande, StatutDemandeConge::PROGRAMMEE);
            }

            return $this->circuit->transitionner($demande, StatutDemandeConge::EN_COURS);
        });
    }
}
