<?php

namespace App\Domain\Contrats\Actions;

use App\Domain\Contrats\Models\AlerteContrat;

class CloturerAlerte
{
    public function executer(AlerteContrat $alerte, ?string $note = null): AlerteContrat
    {
        $alerte->update([
            'en_cours' => false,
            'cloture_le' => now(),
            'cloture_par' => auth()->id() ?? 1,
            'note_cloture' => $note,
        ]);

        return $alerte->fresh();
    }
}