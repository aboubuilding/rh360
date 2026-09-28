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
        return DB::transaction(function () use ($demande, $dateRepriseReelle) {
            if ($dateRepriseReelle) {
                $demande->update(['date_reprise' => Carbon::parse($dateRepriseReelle)]);
            }

            $demande = $this->circuit->transitionner($demande, StatutDemandeConge::REPRISE_CONFIRMEE);

            event(new RepriseConfirmee($demande));

            return $demande;
        });
    }
}