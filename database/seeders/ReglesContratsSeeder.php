<?php

namespace Database\Seeders;

use App\Domain\Classification\Models\CategorieClassification;
use App\Domain\Contrats\Enums\TypeContrat;
use App\Domain\Contrats\Models\RegleContrat;
use Illuminate\Database\Seeder;

class ReglesContratsSeeder extends Seeder
{
    public function run(): void
    {
        $entrepriseId = 1;
        $superAdmin = \App\Domain\Administration\Models\Utilisateur::first();

        $categories = CategorieClassification::whereHas('referentiel', function ($q) use ($entrepriseId) {
            $q->where('entreprise_id', $entrepriseId);
        })->get();

        if ($categories->isEmpty()) {
            $this->command->warn('Aucune catégorie de classification trouvée.');
            return;
        }

        // Règles pour CDI et CDD sur chaque catégorie
        $types = [
            TypeContrat::CDI->value => [
                'duree_max_essai_jours' => 90,
                'duree_max_essai_renouvellement_jours' => 30,
                'nombre_renouvellements_max' => 1,
                'plafond_remuneration' => null,
            ],
            TypeContrat::CDD->value => [
                'duree_max_essai_jours' => 30,
                'duree_max_essai_renouvellement_jours' => 15,
                'nombre_renouvellements_max' => 1,
                'plafond_remuneration' => null,
            ],
            TypeContrat::STAGE->value => [
                'duree_max_essai_jours' => 15,
                'duree_max_essai_renouvellement_jours' => 0,
                'nombre_renouvellements_max' => 0,
                'plafond_remuneration' => 150000,
            ],
        ];

        $compteur = 0;

        foreach ($types as $type => $parametres) {
            foreach ($categories as $categorie) {
                RegleContrat::updateOrCreate(
                    [
                        'entreprise_id' => $entrepriseId,
                        'type_contrat' => $type,
                        'categorie_id' => $categorie->id,
                        'date_effet' => now()->startOfYear(),
                    ],
                    [
                        'parametres' => $parametres,
                        'cree_par' => $superAdmin?->id ?? 1,
                        'etat' => 1,
                    ]
                );
                $compteur++;
            }
        }

        $this->command->info("{$compteur} règles de contrat créées.");
    }
}