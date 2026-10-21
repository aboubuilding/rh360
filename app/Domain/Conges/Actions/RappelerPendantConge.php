<?php

namespace App\Domain\Conges\Actions;

use App\Domain\Conges\Enums\StatutDemandeConge;
use App\Domain\Conges\Models\DemandeConge;
use App\Domain\Conges\Models\HistoriqueDemande;
use App\Domain\Conges\Models\RappelConge;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Rappel d'un salarié pendant son congé : son congé est prolongé des jours ainsi travaillés
 * (CCIT art. 49 b). Les jours de congé restent dus : seule la date de reprise est repoussée.
 */
class RappelerPendantConge
{
    public function executer(DemandeConge $demande, Carbon $du, Carbon $au, string $motif): RappelConge
    {
        if ($demande->statut !== StatutDemandeConge::EN_COURS) {
            throw new \DomainException('Seul un salarié en congé peut être rappelé.');
        }
        if (blank($motif)) {
            throw new \DomainException('Le motif du rappel est obligatoire.');
        }
        $finConge = $demande->date_reprise->copy()->subDay();
        if ($au->lt($du) || $du->lt($demande->date_debut) || $au->gt($finConge)) {
            throw new \DomainException(sprintf(
                'Les jours de rappel doivent être compris dans le congé, du %s au %s.',
                $demande->date_debut->format('d/m/Y'), $finConge->format('d/m/Y'),
            ));
        }

        return DB::transaction(function () use ($demande, $du, $au, $motif) {
            $jours = (int) $du->diffInDays($au) + 1;
            $avant = $demande->date_reprise->copy();
            $apres = $avant->copy()->addDays($jours);

            $rappel = RappelConge::create([
                'entreprise_id' => $demande->entreprise_id,
                'demande_id' => $demande->id,
                'du' => $du,
                'au' => $au,
                'jours' => $jours,
                'motif' => $motif,
                'reprise_avant' => $avant,
                'reprise_apres' => $apres,
                'ordonne_par' => auth()->id(),
            ]);
            $demande->update(['date_reprise' => $apres]);

            HistoriqueDemande::create([
                'entreprise_id' => $demande->entreprise_id,
                'demande_id' => $demande->id,
                'de_statut' => $demande->statut->value,
                'vers_statut' => $demande->statut->value,
                'acteur_id' => auth()->id(),
                'motif' => "Rappel du {$du->format('d/m/Y')} au {$au->format('d/m/Y')} ({$jours} jour(s)) : reprise repoussée au {$apres->format('d/m/Y')}. {$motif}",
            ]);

            return $rappel;
        });
    }
}
