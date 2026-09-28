<?php

namespace App\Domain\Sst\Services;

use App\Domain\Sst\Enums\NaturePieceSst;
use App\Domain\Sst\Models\HistoriqueSst;
use Illuminate\Database\Eloquent\Model;

class GenerateurHistoriqueSst
{
    /**
     * Enregistre une version d'une fiche SST (avant ou après modification).
     */
    public function enregistrer(Model $fiche, NaturePieceSst $nature, int $revision, string $action = 'updated'): HistoriqueSst
    {
        return HistoriqueSst::create([
            'entreprise_id' => $fiche->entreprise_id,
            'nature' => $nature->value,
            'fiche_id' => $fiche->id,
            'revision' => $revision,
            'instantane' => [
                'action' => $action,
                'donnees' => $fiche->toArray(),
                'date' => now()->toIso8601String(),
                'auteur_id' => auth()->id(),
            ],
            'auteur_id' => auth()->id() ?? 1,
        ]);
    }

    /**
     * Récupère tout l'historique d'une fiche, du plus récent au plus ancien.
     */
    public function historique(Model $fiche, NaturePieceSst $nature): \Illuminate\Support\Collection
    {
        return HistoriqueSst::where('nature', $nature->value)
            ->where('fiche_id', $fiche->id)
            ->orderByDesc('revision')
            ->get();
    }
}