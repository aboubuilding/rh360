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

    public function executer(DemandeConge $demande, ?string $referenceActe = null, ?string $dateActe = null, bool $anticipation = false): DemandeConge
    {
        // Congé payé pris sur l'année en cours, avant l'ouverture de ses droits : accord exprès de l'employeur.
        $demande->anticipation = $anticipation;

        // Les règles du type restent respectées au moment de la décision (plafond, chevauchement…).
        app(\App\Domain\Conges\Services\ControleDemande::class)->exiger($demande);

        // Vérifier la disponibilité du solde
        $verif = $this->validateur->verifier($demande);
        if (! $verif['ok']) {
            throw new \DomainException($verif['message'] ?? 'Vérification de solde échouée.');
        }

        $demande->update([
            'anticipation' => $anticipation,
            // Première date de départ fixée : référence du décalage maximum autorisé.
            'date_debut_fixee' => $demande->date_debut_fixee ?? $demande->date_debut,
            'date_decision' => now(),
            'valide_par' => auth()->id(),
            'reference_acte' => $referenceActe,
            'date_acte' => $dateActe ? \Carbon\Carbon::parse($dateActe) : now(),
        ]);

        $demande = $this->circuit->transitionner($demande, StatutDemandeConge::AUTORISEE);
        app(\App\Domain\Conges\Services\GestionJustificatifs::class)->qualifierAbsenceLiee($demande);

        event(new DemandeAutorisee($demande));

        return $demande;
    }
}