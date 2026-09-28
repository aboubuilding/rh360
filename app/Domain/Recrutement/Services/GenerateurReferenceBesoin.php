<?php

namespace App\Domain\Recrutement\Services;

use App\Domain\Recrutement\Models\BesoinRecrutement;

class GenerateurReferenceBesoin
{
    /**
     * Génère une référence unique de type REF-REC-YYYY-NNNN.
     */
    public function generer(int $entrepriseId): string
    {
        $annee = now()->year;
        $prefixe = sprintf('REF-REC-%d-', $annee);

        $dernier = BesoinRecrutement::withoutGlobalScopes()
            ->where('entreprise_id', $entrepriseId)
            ->where('reference', 'like', $prefixe . '%')
            ->orderByDesc('reference')
            ->value('reference');

        $numero = $dernier ? ((int) substr($dernier, -4)) + 1 : 1;

        return $prefixe . str_pad($numero, 4, '0', STR_PAD_LEFT);
    }
}