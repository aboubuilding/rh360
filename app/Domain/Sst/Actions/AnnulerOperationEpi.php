<?php

namespace App\Domain\Sst\Actions;

use App\Domain\Sst\Models\OperationEpi;

class AnnulerOperationEpi
{
    public function executer(OperationEpi $operation, string $motif): OperationEpi
    {
        if (empty(trim($motif))) {
            throw new \DomainException('Le motif d\'annulation est obligatoire.');
        }

        $operation->update([
            'motif_annulation' => $motif,
            'etat' => 0,
        ]);

        return $operation->fresh();
    }
}