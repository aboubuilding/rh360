<?php

namespace App\Domain\Recrutement\Actions;

use App\Domain\Recrutement\Enums\DecisionCandidat;
use App\Domain\Recrutement\Enums\EtapeCandidat;
use App\Domain\Recrutement\Models\Candidat;
use App\Domain\Recrutement\Services\SuiviEtapesCandidat;

class DeciderCandidat
{
    public function __construct(private SuiviEtapesCandidat $suivi) {}

    public function retenir(Candidat $candidat, ?string $commentaire = null): Candidat
    {
        $candidat->update(['decision' => DecisionCandidat::RETENU->value]);
        $candidat = $this->suivi->transitionner($candidat, EtapeCandidat::OFFRE);

        if ($commentaire) {
            $candidat->update(['observations' => $commentaire]);
        }

        return $candidat;
    }

    public function refuser(Candidat $candidat, string $motif): Candidat
    {
        if (empty(trim($motif))) {
            throw new \DomainException('Le motif de refus est obligatoire.');
        }

        $candidat->update(['decision' => DecisionCandidat::REFUSE->value]);
        $candidat = $this->suivi->transitionner($candidat, EtapeCandidat::REFUSE);
        $candidat->update(['observations' => $motif]);

        return $candidat;
    }
}