<?php

namespace App\Domain\Recrutement\Actions;

use App\Domain\Recrutement\Enums\EtapeCandidat;
use App\Domain\Recrutement\Models\Candidat;
use App\Domain\Recrutement\Services\SuiviEtapesCandidat;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class IntegrerCandidat
{
    public function __construct(private SuiviEtapesCandidat $suivi) {}

    public function executer(Candidat $candidat, string $dateIntegration, ?string $matricule = null): Candidat
    {
        if ($candidat->decision?->value !== 'retenu') {
            throw new \DomainException('Seul un candidat retenu peut être intégré.');
        }

        return DB::transaction(function () use ($candidat, $dateIntegration) {
            // Faire évoluer l'étape
            $candidat = $this->suivi->transitionner($candidat, EtapeCandidat::ACCEPTE);

            $candidat->update([
                'date_integration' => Carbon::parse($dateIntegration),
            ]);

            // Si le besoin est pourvu, on peut le marquer
            $besoin = $candidat->besoin;
            if ($besoin && $besoin->estPourvu()) {
                $besoin->update(['statut' => 'pourvu']);
            }

            return $candidat->fresh();
        });
    }
}