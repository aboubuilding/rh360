<?php

namespace App\Domain\Recrutement\Actions;

use App\Domain\Recrutement\Enums\EtapeCandidat;
use App\Domain\Recrutement\Models\Candidat;
use App\Domain\Recrutement\Services\SuiviEtapesCandidat;

class ChangerEtapeCandidat
{
    public function __construct(private SuiviEtapesCandidat $suivi) {}

    public function executer(Candidat $candidat, EtapeCandidat $cible, ?string $observations = null): Candidat
    {
        $candidat = $this->suivi->transitionner($candidat, $cible);

        if ($observations) {
            $candidat->update(['observations' => trim(($candidat->observations ?? '') . "\n" . $observations)]);
        }

        return $candidat;
    }
}