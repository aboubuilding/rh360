<?php

namespace App\Domain\Contrats\Actions;

use App\Domain\Contrats\Enums\NatureEvenementEssai;
use App\Domain\Contrats\Enums\StatutEvenementEssai;
use App\Domain\Contrats\Models\Contrat;
use App\Domain\Contrats\Models\EvenementEssai;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DeclarerEvenementEssai
{
    public function executer(Contrat $contrat, NatureEvenementEssai $nature, array $details): EvenementEssai
    {
        if (! $contrat->estSigne()) {
            throw new \DomainException('Le contrat doit être signé pour déclarer un événement d\'essai.');
        }

        return DB::transaction(function () use ($contrat, $nature, $details) {
            $evenement = EvenementEssai::create([
                'entreprise_id' => $contrat->entreprise_id,
                'contrat_id' => $contrat->id,
                'cle_soumission' => (string) Str::uuid(),
                'nature' => $nature->value,
                'statut' => StatutEvenementEssai::EN_ATTENTE->value,
                'details' => $details,
                'cree_par' => auth()->id() ?? 1,
                'etat' => 1,
            ]);

            return $evenement->fresh();
        });
    }
}