<?php

namespace App\Domain\Sst\Actions;

use App\Domain\Sst\Enums\StatutHabilitation;
use App\Domain\Sst\Models\Habilitation;
use App\Domain\Sst\Services\GenerateurHistoriqueSst;
use App\Domain\Sst\Enums\NaturePieceSst;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RenouvelerHabilitation
{
    public function __construct(private GenerateurHistoriqueSst $historique) {}

    public function executer(Habilitation $origine, array $donnees): Habilitation
    {
        return DB::transaction(function () use ($origine, $donnees) {
            // Marquer l'ancienne comme expirée
            $origine->update([
                'statut' => StatutHabilitation::EXPIREE->value,
                'revision' => $origine->revision + 1,
                'modifie_par' => auth()->id(),
            ]);

            // Créer la nouvelle
            $nouvelle = Habilitation::create([
                'entreprise_id' => $origine->entreprise_id,
                'salarie_id' => $origine->salarie_id,
                'risque_id' => $origine->risque_id,
                'habilitation_origine_id' => $origine->id,
                'cle_soumission' => (string) Str::uuid(),
                'categorie' => $origine->categorie,
                'intitule' => $origine->intitule,
                'portee' => $origine->portee,
                'emetteur' => $donnees['emetteur'] ?? $origine->emetteur,
                'reference_decision' => $donnees['reference_decision'] ?? null,
                'reference_formation' => $donnees['reference_formation'] ?? null,
                'date_decision' => $donnees['date_decision'] ?? now(),
                'date_debut' => $donnees['date_debut'] ?? now(),
                'date_fin' => $donnees['date_fin'] ?? null,
                'date_revue' => $donnees['date_revue'] ?? null,
                'statut' => StatutHabilitation::ACTIVE->value,
                'cree_par' => auth()->id(),
                'modifie_par' => auth()->id(),
                'revision' => 1,
                'etat' => 1,
            ]);

            $this->historique->enregistrer($nouvelle, NaturePieceSst::HABILITATION, 1, 'created');

            return $nouvelle->fresh();
        });
    }
}