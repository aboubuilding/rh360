<?php

namespace App\Domain\Personnel\Services;

use App\Domain\Personnel\Models\Salarie;

class GenerateurMatricule
{
    /**
     * Génère un matricule unique de type MAT-YYYY-NNNN.
     */
    public function generer(int $entrepriseId): string
    {
        $annee = now()->year;
        $prefixe = sprintf('MAT-%d-', $annee);

        $dernier = Salarie::withoutGlobalScopes()
            ->where('entreprise_id', $entrepriseId)
            ->where('matricule', 'like', $prefixe.'%')
            ->orderByDesc('matricule')
            ->value('matricule');

        $numero = $dernier ? ((int) substr($dernier, -4)) + 1 : 1;

        return $prefixe . str_pad($numero, 4, '0', STR_PAD_LEFT);
    }
}