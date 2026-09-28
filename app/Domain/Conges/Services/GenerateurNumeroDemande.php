<?php

namespace App\Domain\Conges\Services;

use App\Domain\Conges\Models\DemandeConge;

class GenerateurNumeroDemande
{
    /**
     * Génère un numéro unique de type DEM-YYYY-NNNNN.
     */
    public function generer(int $entrepriseId): string
    {
        $annee = now()->year;
        $prefixe = sprintf('DEM-%d-', $annee);

        $dernier = DemandeConge::withoutGlobalScopes()
            ->where('entreprise_id', $entrepriseId)
            ->where('numero_demande', 'like', $prefixe.'%')
            ->orderByDesc('numero_demande')
            ->value('numero_demande');

        $numero = $dernier ? ((int) substr($dernier, -5)) + 1 : 1;

        return $prefixe . str_pad($numero, 5, '0', STR_PAD_LEFT);
    }
}