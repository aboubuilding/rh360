<?php

namespace App\Domain\Sst\Actions;

use App\Domain\Sst\Enums\StatutDotationEpi;
use App\Domain\Sst\Models\DotationEpi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EnregistrerDotationEpi
{
    public function executer(array $donnees): DotationEpi
    {
        return DB::transaction(function () use ($donnees) {
            $donnees['entreprise_id'] = auth()->user()->entreprise_id;
            $donnees['cle_soumission'] = (string) Str::uuid();
            $donnees['date_remise'] = $donnees['date_remise'] ?? now();
            $donnees['statut'] = StatutDotationEpi::REMIS->value;
            $donnees['cree_par'] = auth()->id();
            $donnees['modifie_par'] = auth()->id();
            $donnees['revision'] = 1;
            $donnees['etat'] = 1;

            return DotationEpi::create($donnees);
        });
    }
}