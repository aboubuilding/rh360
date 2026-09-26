<?php

namespace App\Domain\Personnel\Actions;

use App\Domain\Personnel\Models\DocumentSalarie;
use App\Domain\Personnel\Models\MembreFoyer;
use App\Domain\Personnel\Models\Salarie;
use Illuminate\Support\Facades\DB;

class FusionnerSalaries
{
    /**
     * Fusionne $source dans $cible.
     * $source est marqué comme fusionné et absorbé ; il n'est pas supprimé.
     */
    public function executer(Salarie $source, Salarie $cible, string $motif): Salarie
    {
        return DB::transaction(function () use ($source, $cible, $motif) {
            // Déplacer les membres du foyer
            MembreFoyer::where('salarie_id', $source->id)
                ->update(['salarie_id' => $cible->id]);

            // Déplacer les documents
            DocumentSalarie::where('salarie_id', $source->id)
                ->update(['salarie_id' => $cible->id]);

            // Marquer la source comme fusionnée
            $source->update([
                'fusionne_dans_salarie_id' => $cible->id,
                'motif_fusion' => $motif,
                'fusionne_le' => now(),
                'actif' => false,
                'statut_emploi' => 'Sorti',
            ]);

            return $cible->fresh();
        });
    }
}