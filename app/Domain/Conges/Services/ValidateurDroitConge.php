<?php

namespace App\Domain\Conges\Services;

use App\Domain\Conges\Models\DemandeConge;
use App\Domain\Conges\Models\SoldeConge;
use App\Domain\Conges\Models\TypeConge;

class ValidateurDroitConge
{
    public function __construct(private CalculateurSolde $calculateurSolde) {}

    /**
     * Vérifie la disponibilité du solde pour une demande.
     *
     * @return array{ok: bool, message?: string, disponible?: float, demande?: float}
     */
    public function verifier(DemandeConge $demande): array
    {
        $type = $demande->typeConge;
        if (! $type) {
            return ['ok' => false, 'message' => 'Type de congé introuvable.'];
        }

        // Si le type n'a pas de droit annuel (permission exceptionnelle),
        // on accepte sans vérification de solde
        if ((float) $type->droit_annuel === 0.0) {
            return ['ok' => true];
        }

        $annee = $demande->date_debut?->year ?? now()->year;
        $solde = $this->calculateurSolde->obtenir($demande->salarie, $type, $annee);

        $dureeDemandee = (float) $demande->duree_jours;

        if (! $this->calculateurSolde->verifierDisponibilite($solde, $dureeDemandee)) {
            return [
                'ok' => false,
                'message' => sprintf(
                    'Solde insuffisant. Disponible : %.2f jours, demandé : %.2f jours.',
                    $solde->disponible,
                    $dureeDemandee,
                ),
                'disponible' => $solde->disponible,
                'demande' => $dureeDemandee,
            ];
        }

        return [
            'ok' => true,
            'disponible' => $solde->disponible,
            'demande' => $dureeDemandee,
        ];
    }

    /**
     * Vérifie les contraintes du type de congé (durée max, justificatif).
     */
    public function verifierContraintesType(DemandeConge $demande): array
    {
        $type = $demande->typeConge;
        if (! $type) return ['ok' => false, 'message' => 'Type introuvable.'];

        if ($type->duree_max && (float) $demande->duree_jours > (float) $type->duree_max) {
            return [
                'ok' => false,
                'message' => sprintf(
                    'La durée demandée (%.2f) dépasse la durée maximale autorisée (%.2f).',
                    $demande->duree_jours,
                    $type->duree_max,
                ),
            ];
        }

        return ['ok' => true];
    }
}