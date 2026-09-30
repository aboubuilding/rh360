<?php

namespace Database\Seeders;

use App\Domain\Classification\Models\CategorieClassification;
use App\Domain\Classification\Models\ClasseClassification;
use App\Domain\Classification\Models\EchelonClassification;
use App\Domain\Classification\Models\PositionClassification;
use App\Domain\Classification\Models\ReferentielClassification;
use App\Domain\Classification\Models\RegleEvolution;
use Illuminate\Database\Seeder;

class ReferentielClassificationSeeder extends Seeder
{
    public function run(): void
    {
        $referentiel = ReferentielClassification::updateOrCreate(
            ['entreprise_id' => 1, 'code' => 'GRILLE-INTERNE'],
            [
                'entreprise_id' => 1,
                'nom' => 'Grille interne EXPERT RH 360',
                'type_referentiel' => 'enterprise',
                'niveau_source' => 'interne',
                'priorite' => 1,
                'actif' => true,
                'etat' => 1,
            ]
        );

        // Catégories
        foreach (['A' => 'Agent', 'B' => 'Technicien', 'C' => 'Cadre'] as $code => $libelle) {
            CategorieClassification::updateOrCreate(
                ['referentiel_id' => $referentiel->id, 'code' => $code],
                ['libelle' => $libelle, 'ordre' => ord($code) - 64, 'actif' => true, 'etat' => 1]
            );
        }

        // Classes
        foreach (['1', '2', '3'] as $code) {
            ClasseClassification::updateOrCreate(
                ['referentiel_id' => $referentiel->id, 'code' => $code],
                ['libelle' => "Classe $code", 'ordre' => (int) $code, 'actif' => true, 'etat' => 1]
            );
        }

        // Échelons
        foreach (['A', 'B', 'C', 'D'] as $i => $code) {
            EchelonClassification::updateOrCreate(
                ['referentiel_id' => $referentiel->id, 'code' => $code],
                ['libelle' => "Échelon $code", 'ordre' => $i + 1, 'actif' => true, 'etat' => 1]
            );
        }

        // Positions de grille : catégorie – classe – échelon, chaînées d'échelon en échelon
        $categories = CategorieClassification::where('referentiel_id', $referentiel->id)->orderBy('ordre')->get();
        $classes = ClasseClassification::where('referentiel_id', $referentiel->id)->orderBy('ordre')->get();
        $echelons = EchelonClassification::where('referentiel_id', $referentiel->id)->orderBy('ordre')->get();

        $ordre = 0;
        foreach ($categories as $ic => $categorie) {
            foreach ($classes as $ik => $classe) {
                $suivante = null;
                // Parcours inverse pour connaître la position suivante (échelon supérieur)
                foreach ($echelons->reverse() as $ie => $echelon) {
                    $salaire = 80000 + $ic * 60000 + $ik * 20000 + $ie * 5000;
                    $suivante = PositionClassification::updateOrCreate(
                        ['referentiel_id' => $referentiel->id, 'code' => "{$categorie->code}{$classe->code}-{$echelon->code}"],
                        [
                            'categorie_id' => $categorie->id,
                            'classe_id' => $classe->id,
                            'echelon_id' => $echelon->id,
                            'montant_salaire' => $salaire,
                            'salaire_minimum' => $salaire,
                            'ordre' => ++$ordre,
                            'position_suivante_id' => $suivante?->id,
                            'actif' => true,
                            'statut' => 'active',
                            'etat' => 1,
                        ]
                    );
                }
            }
        }

        // Règle d'avancement d'échelon : 24 mois minimum
        RegleEvolution::updateOrCreate(
            ['referentiel_id' => $referentiel->id, 'code' => 'AV-ECHELON'],
            [
                'type_evolution' => 'echelon',
                'niveau_source' => 'interne',
                'priorite' => 1,
                'mois_min' => 24,
                'mois_max' => 36,
                'anticipation_autorisee' => true,
                'validation_requise' => true,
                'actif' => true,
                'statut' => 'active',
                'etat' => 1,
            ]
        );
    }
}