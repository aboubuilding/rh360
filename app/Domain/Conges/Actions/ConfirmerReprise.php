<?php

namespace App\Domain\Conges\Actions;

use App\Domain\Conges\Enums\StatutDemandeConge;
use App\Domain\Conges\Events\RepriseConfirmee;
use App\Domain\Conges\Models\DemandeConge;
use App\Domain\Conges\Services\CircuitDemandeConge;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ConfirmerReprise
{
    public function __construct(private CircuitDemandeConge $circuit) {}

    public function executer(DemandeConge $demande, ?string $dateRepriseReelle = null): DemandeConge
    {
        // CDC §4.1 : la reprise se confirme à partir de la date prévue, pas avant.
        if (! $demande->date_reprise || $demande->date_reprise->isFuture()) {
            throw new \DomainException(
                'La reprise ne peut être confirmée qu\'à partir de la date prévue ('
                .($demande->date_reprise?->format('d/m/Y') ?? 'non renseignée').').'
            );
        }

        return DB::transaction(function () use ($demande, $dateRepriseReelle) {
            // Congé programmé dont le départ est passé sans bascule automatique : il est en cours.
            if ($demande->statut === StatutDemandeConge::PROGRAMMEE) {
                $demande = $this->circuit->transitionner($demande, StatutDemandeConge::EN_COURS);
            }

            if ($dateRepriseReelle) {
                $demande->update(['date_reprise' => Carbon::parse($dateRepriseReelle)]);
            }

            $demande = $this->circuit->transitionner($demande, StatutDemandeConge::REPRISE_CONFIRMEE);

            event(new RepriseConfirmee($demande));

            return $demande;
        });
    }
}