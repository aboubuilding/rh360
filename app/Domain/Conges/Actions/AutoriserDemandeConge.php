<?php

namespace App\Domain\Conges\Actions;

use App\Domain\Conges\Enums\StatutDemandeConge;
use App\Domain\Conges\Events\DemandeAutorisee;
use App\Domain\Conges\Models\DemandeConge;
use App\Domain\Conges\Services\CircuitDemandeConge;
use App\Domain\Conges\Services\ValidateurDroitConge;

class AutoriserDemandeConge
{
    public function __construct(
        private CircuitDemandeConge $circuit,
        private ValidateurDroitConge $validateur,
    ) {}

    public function executer(DemandeConge $demande, ?string $referenceActe = null, ?string $dateActe = null): DemandeConge
    {
        // Vérifier la disponibilité du solde
        $verif = $this->validateur->verifier($demande);
        if (! $verif['ok']) {
            throw new \DomainException($verif['message'] ?? 'Vérification de solde échouée.');
        }

        $demande->update([
            'date_decision' => now(),
            'valide_par' => auth()->id(),
            'reference_acte' => $referenceActe,
            'date_acte' => $dateActe ? \Carbon\Carbon::parse($dateActe) : now(),
        ]);

        $demande = $this->circuit->transitionner($demande, StatutDemandeConge::AUTORISEE);

        event(new DemandeAutorisee($demande));

        return $demande;
    }
}