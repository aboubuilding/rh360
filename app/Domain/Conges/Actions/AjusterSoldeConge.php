<?php

namespace App\Domain\Conges\Actions;

use App\Domain\Conges\Models\SoldeConge;

class AjusterSoldeConge
{
    public function executer(SoldeConge $solde, float $ajustement, string $motif): SoldeConge
    {
        if (empty(trim($motif))) {
            throw new \DomainException('Le motif de l\'ajustement est obligatoire.');
        }

        $solde->update([
            'ajustement' => $solde->ajustement + $ajustement,
            'observations' => trim(($solde->observations ?? '') . "\n" . now()->format('d/m/Y') . ' : ' . $motif . ' (' . ($ajustement >= 0 ? '+' : '') . $ajustement . ')'),
        ]);

        return $solde->fresh();
    }
}