<?php

namespace App\Domain\Carriere\Services;

use App\Domain\Carriere\Enums\StatutMouvement;
use App\Domain\Carriere\Models\MouvementCarriere;
use Illuminate\Support\Facades\DB;

class CircuitMouvement
{
    /**
     * Table de transitions autorisées.
     */
    private const TRANSITIONS = [
        'draft'      => ['proposed', 'cancelled'],
        'proposed'   => ['to_check', 'draft', 'cancelled'],
        'to_check'   => ['checked', 'draft', 'rejected', 'cancelled'],
        'checked'    => ['validated', 'rejected', 'cancelled'],
        'validated'  => ['scheduled', 'rejected', 'cancelled'],
        'scheduled'  => ['effective', 'cancelled'],
        'effective'  => ['completed'],
        'completed'  => [],
        'rejected'   => [],
        'cancelled'  => [],
    ];

    public function peutTransitionner(StatutMouvement $avant, StatutMouvement $apres): bool
    {
        return in_array($apres->value, self::TRANSITIONS[$avant->value] ?? [], true);
    }

    public function transitionner(
        MouvementCarriere $mouvement,
        StatutMouvement $cible,
        string $action,
        ?string $motif = null,
    ): MouvementCarriere {
        if (! $this->peutTransitionner($mouvement->statut, $cible)) {
            throw new \DomainException(
                "Transition invalide : {$mouvement->statut->value} → {$cible->value}"
            );
        }

        return DB::transaction(function () use ($mouvement, $cible, $action, $motif) {
            $avant = $mouvement->statut;
            $mouvement->statut = $cible;
            $mouvement->save();

            // Journaliser dans le journal_audit (via ServiceAudit)
            app(\App\Domain\Administration\Services\ServiceAudit::class)->tracer(
                "carriere.{$action}",
                $mouvement,
                [
                    'statut_avant' => $avant->value,
                    'statut_apres' => $cible->value,
                    'motif' => $motif,
                ]
            );

            return $mouvement->fresh();
        });
    }
}