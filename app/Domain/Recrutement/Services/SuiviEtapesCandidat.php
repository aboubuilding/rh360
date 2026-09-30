<?php

namespace App\Domain\Recrutement\Services;

use App\Domain\Recrutement\Enums\EtapeCandidat;
use App\Domain\Recrutement\Models\Candidat;
use Illuminate\Support\Collection;

/**
 * Pipeline de recrutement : candidature → présélection → entretiens / test → offre → accepté.
 * Un candidat peut être refusé ou se retirer à toute étape non finale ; une étape finale
 * (accepté, refusé, retiré) ne peut plus évoluer.
 */
class SuiviEtapesCandidat
{
    private const TRANSITIONS = [
        'candidature_recue' => ['preselection'],
        'preselection'      => ['entretien_1', 'test', 'offre'],
        'entretien_1'       => ['entretien_2', 'test', 'offre'],
        'entretien_2'       => ['test', 'offre'],
        'test'              => ['entretien_1', 'entretien_2', 'offre'],
        'offre'             => ['accepte'],
        'accepte'           => [],
        'refuse'            => [],
        'retire'            => [],
    ];

    public function peutTransitionner(EtapeCandidat $depuis, EtapeCandidat $vers): bool
    {
        if ($depuis->estFinal()) {
            return false;
        }

        if (in_array($vers, [EtapeCandidat::REFUSE, EtapeCandidat::RETIRE], true)) {
            return true;
        }

        return in_array($vers->value, self::TRANSITIONS[$depuis->value], true);
    }

    /**
     * @return Collection<int, EtapeCandidat> étapes atteignables depuis l'étape courante
     */
    public function etapesPossibles(Candidat $candidat): Collection
    {
        return collect(EtapeCandidat::cases())
            ->filter(fn (EtapeCandidat $e) => $this->peutTransitionner($candidat->etape, $e))
            ->values();
    }

    public function transitionner(Candidat $candidat, EtapeCandidat $cible): Candidat
    {
        if (! $this->peutTransitionner($candidat->etape, $cible)) {
            throw new \DomainException(
                "Transition d'étape invalide : {$candidat->etape->libelle()} → {$cible->libelle()}"
            );
        }

        $candidat->update(['etape' => $cible->value]);

        return $candidat->fresh();
    }
}
