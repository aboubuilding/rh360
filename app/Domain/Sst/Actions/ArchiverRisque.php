<?php

namespace App\Domain\Sst\Actions;

use App\Domain\Sst\Models\Risque;
use App\Domain\Sst\Services\GenerateurHistoriqueSst;
use App\Domain\Sst\Enums\NaturePieceSst;

class ArchiverRisque
{
    public function __construct(private GenerateurHistoriqueSst $historique) {}

    public function executer(Risque $risque, string $motif): Risque
    {
        if (empty(trim($motif))) {
            throw new \DomainException('Le motif d\'archivage est obligatoire.');
        }

        $risque->update([
            'statut' => 'archived',
            'motif_archivage' => $motif,
            'revision' => $risque->revision + 1,
            'modifie_par' => auth()->id(),
        ]);

        $this->historique->enregistrer($risque, NaturePieceSst::RISQUE, $risque->revision, 'archived');

        return $risque->fresh();
    }
}