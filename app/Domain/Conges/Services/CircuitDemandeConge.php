<?php

namespace App\Domain\Conges\Services;

use App\Domain\Conges\Enums\StatutDemandeConge;
use App\Domain\Conges\Models\DemandeConge;
use App\Domain\Conges\Models\HistoriqueDemande;
use Illuminate\Support\Facades\DB;

class CircuitDemandeConge
{
    public function __construct(private CalculateurSolde $calculateurSolde) {}

    public function transitionner(
        DemandeConge $demande,
        StatutDemandeConge $cible,
        ?string $motif = null,
        bool $automatique = false,
    ): DemandeConge {
        if (! $demande->statut->peutTransitionnerVers($cible)) {
            throw new \DomainException(
                "Transition invalide : {$demande->statut->value} → {$cible->value}"
            );
        }

        return DB::transaction(function () use ($demande, $cible, $motif, $automatique) {
            $avant = $demande->statut;
            $demande->statut = $cible;
            $demande->save();

            HistoriqueDemande::create([
                'entreprise_id' => $demande->entreprise_id,
                'demande_id' => $demande->id,
                'de_statut' => $avant->value,
                'vers_statut' => $cible->value,
                'automatique' => $automatique,
                'acteur_id' => auth()->id(),
                'motif' => $motif,
            ]);

            // Resynchroniser le solde à chaque changement d'état
            $this->resynchroniserSolde($demande);

            // Tracé
            app(\App\Domain\Administration\Services\ServiceAudit::class)->tracer(
                'conge.transition',
                $demande,
                [
                    'statut_avant' => $avant->value,
                    'statut_apres' => $cible->value,
                    'motif' => $motif,
                ]
            );

            return $demande->fresh();
        });
    }

    private function resynchroniserSolde(DemandeConge $demande): void
    {
        $annee = $demande->date_debut?->year ?? now()->year;

        $solde = \App\Domain\Conges\Models\SoldeConge::where('salarie_id', $demande->salarie_id)
            ->where('type_conge_id', $demande->type_conge_id)
            ->where('annee', $annee)
            ->first();

        if ($solde) {
            $this->calculateurSolde->resynchroniser($solde);
        }
    }
}