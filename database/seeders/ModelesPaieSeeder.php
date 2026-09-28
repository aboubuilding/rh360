<?php

namespace Database\Seeders;

use App\Domain\Classification\Models\CategorieClassification;
use App\Domain\Paie\Enums\ModeCalculRubrique;
use App\Domain\Paie\Enums\NatureRubrique;
use App\Domain\Paie\Enums\RecurrenceRubrique;
use App\Domain\Paie\Enums\TraitementFiscal;
use App\Domain\Paie\Models\ModelePaie;
use App\Domain\Paie\Models\ModelePaieRubrique;
use App\Domain\Paie\Models\RubriquePaie;
use Illuminate\Database\Seeder;

class ModelesPaieSeeder extends Seeder
{
    public function run(): void
    {
        $entrepriseId = 1;

        $categories = CategorieClassification::whereHas('referentiel', function ($q) use ($entrepriseId) {
            $q->where('entreprise_id', $entrepriseId);
        })->get();

        if ($categories->isEmpty()) {
            $this->command->warn('Aucune catégorie de classification. Exécutez ReferentielClassificationSeeder d\'abord.');
            return;
        }

        $rubriques = RubriquePaie::where('entreprise_id', $entrepriseId)->get()->keyBy('code');

        $codesModeleBase = [
            'SAL-BASE' => ['montant_defaut' => 0, 'obligatoire' => true, 'ordre' => 10],
            'PRIME-TRANSP' => ['montant_defaut' => 15000, 'obligatoire' => false, 'ordre' => 20],
            'PRIME-LOGT' => ['montant_defaut' => 30000, 'obligatoire' => false, 'ordre' => 30],
            'PRIME-ANC' => ['montant_defaut' => 0, 'obligatoire' => false, 'ordre' => 40],
            'CNSS' => ['montant_defaut' => 0, 'obligatoire' => true, 'ordre' => 60],
            'AMU' => ['montant_defaut' => 0, 'obligatoire' => true, 'ordre' => 70],
            'IRPP' => ['montant_defaut' => 0, 'obligatoire' => true, 'ordre' => 90],
        ];

        $compteur = 0;

        foreach ($categories as $categorie) {
            $modele = ModelePaie::updateOrCreate(
                ['entreprise_id' => $entrepriseId, 'categorie_id' => $categorie->id],
                [
                    'entreprise_id' => $entrepriseId,
                    'nom' => 'Modèle standard — ' . $categorie->libelle,
                    'actif' => true,
                    'etat' => 1,
                ]
            );

            // Supprimer les anciennes rubriques pour reconstruire proprement
            $modele->rubriques()->delete();

            foreach ($codesModeleBase as $code => $config) {
                $rubrique = $rubriques->get($code);
                if (! $rubrique) continue;

                ModelePaieRubrique::create([
                    'entreprise_id' => $entrepriseId,
                    'modele_id' => $modele->id,
                    'rubrique_id' => $rubrique->id,
                    'montant_defaut' => $config['montant_defaut'],
                    'obligatoire' => $config['obligatoire'],
                    'ordre' => $config['ordre'],
                    'actif' => true,
                    'etat' => 1,
                ]);
            }

            $compteur++;
        }

        $this->command->info("{$compteur} modèle(s) de paie créé(s).");
    }
}