<?php

namespace App\Domain\Contrats\Services;

use App\Domain\Contrats\Enums\StatutContrat;
use App\Domain\Contrats\Models\Contrat;
use App\Domain\Contrats\Models\HistoriqueContrat;
use Illuminate\Support\Facades\DB;

class CircuitContrat
{
    /**
     * Enregistre une transition dans l'historique + met à jour le statut.
     */
    public function transitionner(
        Contrat $contrat,
        StatutContrat $cible,
        string $action,
        ?string $motif = null,
    ): Contrat {
        if (! $contrat->statut->peutTransitionnerVers($cible)) {
            throw new \DomainException(
                "Transition invalide : {$contrat->statut->value} → {$cible->value}"
            );
        }

        return DB::transaction(function () use ($contrat, $cible, $action, $motif) {
            $avant = $contrat->statut;
            $contrat->statut = $cible;
            $contrat->save();

            HistoriqueContrat::create([
                'entreprise_id' => $contrat->entreprise_id,
                'contrat_id' => $contrat->id,
                'action' => $action,
                'instantane' => [
                    'statut_avant' => $avant->value,
                    'statut_apres' => $cible->value,
                    'motif' => $motif,
                    'utilisateur_id' => auth()->id(),
                    'date' => now()->toIso8601String(),
                ],
                'utilisateur_id' => auth()->id() ?? 1,
            ]);

            return $contrat->fresh();
        });
    }

    public function peutEtreModifie(Contrat $contrat): bool
    {
        return $contrat->estModifiable();
    }

    public function peutEtreSupprime(Contrat $contrat): bool
    {
        return $contrat->statut === StatutContrat::BROUILLON;
    }
}