<?php

namespace App\Domain\Sst\Actions;

use App\Domain\Sst\Enums\NatureOperationEpi;
use App\Domain\Sst\Enums\StatutDotationEpi;
use App\Domain\Sst\Models\DotationEpi;
use App\Domain\Sst\Models\OperationEpi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EnregistrerOperationEpi
{
    public function executer(DotationEpi $dotation, array $donnees): OperationEpi
    {
        return DB::transaction(function () use ($dotation, $donnees) {
            $nature = NatureOperationEpi::from($donnees['nature']);

            $operation = OperationEpi::create([
                'entreprise_id' => $dotation->entreprise_id,
                'dotation_id' => $dotation->id,
                'cle_soumission' => (string) Str::uuid(),
                'nature' => $nature->value,
                'date_evenement' => $donnees['date_evenement'] ?? now(),
                'quantite' => $donnees['quantite'] ?? null,
                'date_prochaine_verification' => $donnees['date_prochaine_verification'] ?? null,
                'intervenant' => $donnees['intervenant'] ?? auth()->user()->nom_complet,
                'resultat' => $donnees['resultat'] ?? '—',
                'cree_par' => auth()->id(),
                'etat' => 1,
            ]);

            // Mettre à jour le statut de la dotation selon la nature
            $nouveauStatut = match ($nature) {
                NatureOperationEpi::RESTITUTION   => StatutDotationEpi::RESTITUE,
                NatureOperationEpi::PERTE         => StatutDotationEpi::PERDU,
                NatureOperationEpi::MISE_AU_REBUT => StatutDotationEpi::REBUTE,
                NatureOperationEpi::VERIFICATION  => StatutDotationEpi::EN_USAGE,
                NatureOperationEpi::REMPLACEMENT  => StatutDotationEpi::A_REMPLACER,
            };

            $dotation->update([
                'statut' => $nouveauStatut->value,
                'revision' => $dotation->revision + 1,
                'modifie_par' => auth()->id(),
            ]);

            return $operation->fresh();
        });
    }
}