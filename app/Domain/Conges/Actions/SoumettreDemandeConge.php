<?php

namespace App\Domain\Conges\Actions;

use App\Domain\Conges\Enums\StatutDemandeConge;
use App\Domain\Conges\Models\DemandeConge;
use App\Domain\Conges\Services\CircuitDemandeConge;
use App\Domain\Conges\Services\GestionJustificatifs;
use App\Domain\Conges\Services\ValidateurDroitConge;
use Illuminate\Support\Facades\DB;

class SoumettreDemandeConge
{
    public function __construct(
        private CircuitDemandeConge $circuit,
        private ValidateurDroitConge $validateur,
        private GestionJustificatifs $justificatifs,
    ) {}

    public function executer(DemandeConge $demande): DemandeConge
    {
        // Vérifier les contraintes du type
        $contraintes = $this->validateur->verifierContraintesType($demande);
        if (! $contraintes['ok']) {
            throw new \DomainException($contraintes['message']);
        }

        // Autorisation préalable : la demande est déposée avant l'absence, sauf force majeure (CCIT art. 50).
        $type = $demande->typeConge;
        $depot = ($demande->date_demande ?? today())->copy()->startOfDay();
        if ($type->necessiteAutorisation() && ! $demande->force_majeure && $demande->date_debut->lt($depot)) {
            throw new \DomainException('Cette absence doit être demandée avant son début. S\'il s\'agit d\'un cas de force majeure, indiquez-le pour la régulariser.');
        }
        if ($demande->force_majeure && blank($demande->motif_force_majeure)) {
            throw new \DomainException('Précisez le cas de force majeure.');
        }

        return DB::transaction(function () use ($demande) {
            $demande->update(['echeance_justificatif' => $this->justificatifs->echeance($demande)]);
            $demande = $this->circuit->transitionner($demande, StatutDemandeConge::SOUMISE);

            return $this->justificatifs->orienter($demande);
        });
    }
}