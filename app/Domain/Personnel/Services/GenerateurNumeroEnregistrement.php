<?php

namespace App\Domain\Personnel\Services;

use App\Domain\Personnel\Models\Salarie;

class GenerateurNumeroEnregistrement
{
    /**
     * Génère un numéro d'enregistrement unique de type ENR-YYYY-NNNNNN.
     */
    public function generer(int $entrepriseId): string
    {
        $annee = now()->year;
        $prefixe = sprintf('ENR-%d-', $annee);

        $dernier = Salarie::withoutGlobalScopes()
            ->where('entreprise_id', $entrepriseId)
            ->where('numero_enregistrement', 'like', $prefixe.'%')
            ->orderByDesc('numero_enregistrement')
            ->value('numero_enregistrement');

        $numero = $dernier ? ((int) substr($dernier, -6)) + 1 : 1;

        return $prefixe . str_pad($numero, 6, '0', STR_PAD_LEFT);
    }
}