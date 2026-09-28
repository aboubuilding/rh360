<?php

namespace App\Domain\Carriere\Services;

use App\Domain\Carriere\Models\MouvementCarriere;

class GenerateurNumeroMouvement
{
    /**
     * Génère un numéro unique de type MOV-YYYY-NNNNN.
     */
    public function generer(int $entrepriseId): string
    {
        $annee = now()->year;
        $prefixe = sprintf('MOV-%d-', $annee);

        $dernier = MouvementCarriere::withoutGlobalScopes()
            ->where('entreprise_id', $entrepriseId)
            ->where('numero_mouvement', 'like', $prefixe.'%')
            ->orderByDesc('numero_mouvement')
            ->value('numero_mouvement');

        $numero = $dernier ? ((int) substr($dernier, -5)) + 1 : 1;

        return $prefixe . str_pad($numero, 5, '0', STR_PAD_LEFT);
    }
}