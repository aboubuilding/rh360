<?php

namespace App\Domain\Conges\Actions;

use App\Domain\Conges\Enums\StatutDemandeConge;
use App\Domain\Conges\Events\DemandeRefusee;
use App\Domain\Conges\Models\DemandeConge;
use App\Domain\Conges\Services\CircuitDemandeConge;

class RefuserDemandeConge
{
    public function __construct(private CircuitDemandeConge $circuit) {}

    public function executer(DemandeConge $demande, string $motif): DemandeConge
    {
        if (empty(trim($motif))) {
            throw new \DomainException('Le motif de refus est obligatoire.');
        }

        // Absence de droit : elle ne se refuse pas. Sans justificatif conforme, elle est requalifiée.
        if ($demande->typeConge?->estDeDroit()) {
            throw new \DomainException('Cette absence est de droit : elle ne peut pas être refusée. Si le justificatif manque ou n\'est pas conforme, elle sera requalifiée à l\'échéance.');
        }

        $demande->update([
            'date_decision' => now(),
            'valide_par' => auth()->id(),
        ]);

        $demande = $this->circuit->transitionner($demande, StatutDemandeConge::REFUSEE, $motif);

        event(new DemandeRefusee($demande, $motif));

        return $demande;
    }
}