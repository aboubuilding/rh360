<?php

namespace Database\Factories;

use App\Domain\Personnel\Models\Salarie;
use App\Domain\Sst\Enums\CategorieEpi;
use App\Domain\Sst\Enums\StatutDotationEpi;
use App\Domain\Sst\Models\DotationEpi;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class DotationEpiFactory extends Factory
{
    protected $model = DotationEpi::class;

    public function definition(): array
    {
        return [
            'entreprise_id' => 1,
            'salarie_id' => Salarie::factory(),
            'cle_soumission' => (string) Str::uuid(),
            'categorie' => CategorieEpi::TETE->value,
            'intitule' => 'Casque de chantier',
            'quantite' => 1,
            'unite' => 'unité',
            'date_remise' => now()->subMonth(),
            'date_expiration' => now()->addYear(),
            'emetteur' => 'Service HSE',
            'statut' => StatutDotationEpi::REMIS->value,
            'cree_par' => 1,
            'modifie_par' => 1,
            'revision' => 1,
            'etat' => 1,
        ];
    }
}
