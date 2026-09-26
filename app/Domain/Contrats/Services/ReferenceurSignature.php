<?php

namespace App\Domain\Contrats\Services;

use App\Domain\Contrats\Enums\StatutContrat;
use App\Domain\Contrats\Models\Contrat;
use Carbon\Carbon;

class ReferenceurSignature
{
    public function referencer(
        Contrat $contrat,
        Carbon $dateSignature,
        string $referenceSignee,
    ): Contrat {
        if ($contrat->statut !== StatutContrat::VALIDE) {
            throw new \DomainException('Seul un contrat validé peut être signé.');
        }

        $contrat->update([
            'statut' => StatutContrat::SIGNE,
            'date_signature' => $dateSignature,
            'reference_signee' => $referenceSignee,
        ]);

        // Mettre à jour les champs legacy du salarié (lecture seule conservée)
        $contrat->salarie->update([
            'type_contrat' => $contrat->type_contrat,
            'reference_contrat' => $referenceSignee,
            'date_contrat' => $dateSignature,
            'date_fin_contrat' => $contrat->date_fin,
        ]);

        return $contrat->fresh();
    }
}