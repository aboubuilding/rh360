<?php

namespace App\Domain\Conges\Actions;

use App\Domain\Conges\Enums\StatutDemandeConge;
use App\Domain\Conges\Models\DemandeConge;
use App\Domain\Conges\Services\CircuitDemandeConge;

class ProgrammerDemandeConge
{
    public function __construct(private CircuitDemandeConge $circuit) {}

    // Avertissement à afficher quand l'ordre de départ est communiqué trop tard mais seulement signalé.
    public ?string $avertissement = null;

    /**
     * Programmer le départ, c'est communiquer l'ordre de départ. Un départ fixé par l'employeur
     * est communiqué au moins 15 jours à l'avance (CCIT art. 49 b) : à défaut, il est refusé ou
     * seulement signalé, selon le paramétrage.
     */
    public function executer(DemandeConge $demande): DemandeConge
    {
        $parametres = app(\App\Domain\Conges\Services\ParametresDemandes::class)->pourEntreprise($demande->entreprise_id);
        $delai = (int) $parametres->get('conge_delai_ordre_depart_jours');
        $communication = today();

        if ($demande->initiative === 'employeur' && $demande->date_debut
            && (int) $communication->diffInDays($demande->date_debut->copy()->startOfDay(), false) < $delai) {
            $message = sprintf(
                'L\'ordre de départ doit être communiqué au moins %d jours avant le départ, soit au plus tard le %s.',
                $delai, $demande->date_debut->copy()->subDays($delai)->format('d/m/Y'),
            );
            if ($parametres->get('conge_ordre_depart_tardif') === 'bloquer') {
                throw new \DomainException($message.' Reportez la date de départ ou obtenez l\'accord du salarié sur une demande à son initiative.');
            }
            $this->avertissement = $message;
        }

        $demande->update(['ordre_depart_le' => $demande->ordre_depart_le ?? $communication]);

        return $this->circuit->transitionner($demande, StatutDemandeConge::PROGRAMMEE, $this->avertissement);
    }
}