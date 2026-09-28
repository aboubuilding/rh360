<?php

namespace App\Domain\Sst\Actions;

use App\Domain\Sst\Enums\StatutHabilitation;
use App\Domain\Sst\Models\Habilitation;

class RevoquerHabilitation
{
    public function executer(Habilitation $habilitation, string $motif): Habilitation
    {
        if (empty(trim($motif))) {
            throw new \DomainException('Le motif de révocation est obligatoire.');
        }

        $habilitation->update([
            'statut' => StatutHabilitation::REVOQUEE->value,
            'motif' => $motif,
            'revision' => $habilitation->revision + 1,
            'modifie_par' => auth()->id(),
        ]);

        return $habilitation->fresh();
    }
}