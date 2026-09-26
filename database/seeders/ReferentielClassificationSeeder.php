<?php

namespace Database\Seeders;

use App\Domain\Classification\Models\CategorieClassification;
use App\Domain\Classification\Models\ClasseClassification;
use App\Domain\Classification\Models\EchelonClassification;
use App\Domain\Classification\Models\ReferentielClassification;
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
    }
}