<?php

namespace App\Domain\Classification\Services;

use App\Domain\Classification\Models\CategorieClassification;
use App\Domain\Classification\Models\ClasseClassification;
use App\Domain\Classification\Models\EchelonClassification;
use App\Domain\Classification\Models\PositionClassification;
use App\Domain\Classification\Models\ReferentielClassification;
use Illuminate\Support\Facades\DB;

/**
 * Installe, pour une entreprise, le référentiel de la CCIT 2026 : trois classes (Annexe I),
 * catégories E1 à E6, M1 à M4, C1 à C4 et un hors catégorie par classe, échelons A, B, C
 * (échelon unique pour E1), et salaires de base minimums de l'Annexe II.
 * Idempotent : une nouvelle exécution met à jour les montants sans dupliquer.
 * Les lignes hors catégorie ne sont pas chiffrées : leurs montants sont fixés par l'employeur.
 */
class InstallateurBaremeCcit2026
{
    public const CODE_REFERENTIEL = 'CCIT-2026';

    public const DATE_EFFET = '2026-05-20';

    /** Annexe II — salaire de base minimum (FCFA) par catégorie et échelon. */
    public const BAREME = [
        'E1' => ['U' => 52500],
        'E2' => ['A' => 57500, 'B' => 60500, 'C' => 63500],
        'E3' => ['A' => 64500, 'B' => 67500, 'C' => 70500],
        'E4' => ['A' => 71500, 'B' => 75500, 'C' => 79500],
        'E5' => ['A' => 80500, 'B' => 84500, 'C' => 88500],
        'E6' => ['A' => 89500, 'B' => 93500, 'C' => 97500],
        'M1' => ['A' => 103500, 'B' => 108500, 'C' => 113500],
        'M2' => ['A' => 115000, 'B' => 120000, 'C' => 125000],
        'M3' => ['A' => 126500, 'B' => 132500, 'C' => 138500],
        'M4' => ['A' => 140000, 'B' => 146000, 'C' => 152000],
        'C1' => ['A' => 161500, 'B' => 168500, 'C' => 175500],
        'C2' => ['A' => 181500, 'B' => 188500, 'C' => 195500],
        'C3' => ['A' => 202500, 'B' => 209500, 'C' => 216500],
        'C4' => ['A' => 226500, 'B' => 236500, 'C' => 246500],
    ];

    /** Annexe I — classes et catégories, hors catégorie en fin de classe. */
    private const CLASSES = [
        'I' => ['libelle' => 'Agents d\'exécution', 'categories' => ['E1', 'E2', 'E3', 'E4', 'E5', 'E6'], 'hc' => 'HC-E'],
        'II' => ['libelle' => 'Agents de maîtrise', 'categories' => ['M1', 'M2', 'M3', 'M4'], 'hc' => 'HC-M'],
        'III' => ['libelle' => 'Cadres', 'categories' => ['C1', 'C2', 'C3', 'C4'], 'hc' => 'HC-C'],
    ];

    private const ECHELONS = ['U' => 'Échelon unique', 'A' => 'Échelon A', 'B' => 'Échelon B', 'C' => 'Échelon C', 'HC' => 'Hors catégorie'];

    /**
     * @return array{referentiel: ReferentielClassification, positions: int}
     */
    public function installer(int $entrepriseId): array
    {
        return DB::transaction(function () use ($entrepriseId) {
            $referentiel = ReferentielClassification::withoutGlobalScopes()->updateOrCreate(
                ['entreprise_id' => $entrepriseId, 'code' => self::CODE_REFERENTIEL],
                [
                    'nom' => 'Classification et barème 2026',
                    'type_referentiel' => 'conformity',
                    'niveau_source' => 'ccit',
                    'priorite' => 100,
                    'debut_effet' => self::DATE_EFFET,
                    'actif' => true,
                    'etat' => 1,
                ],
            );

            $echelons = [];
            $ordre = 0;
            foreach (self::ECHELONS as $code => $libelle) {
                $echelons[$code] = EchelonClassification::withoutGlobalScopes()->updateOrCreate(
                    ['referentiel_id' => $referentiel->id, 'code' => $code],
                    ['libelle' => $libelle, 'ordre' => ++$ordre, 'actif' => true, 'etat' => 1],
                );
            }

            $positions = 0;
            $ordreCategorie = 0;
            $ordreClasse = 0;
            foreach (self::CLASSES as $codeClasse => $classe) {
                $modeleClasse = ClasseClassification::withoutGlobalScopes()->updateOrCreate(
                    ['referentiel_id' => $referentiel->id, 'code' => $codeClasse],
                    ['libelle' => $classe['libelle'], 'ordre' => ++$ordreClasse, 'actif' => true, 'etat' => 1],
                );

                foreach ([...$classe['categories'], $classe['hc']] as $codeCategorie) {
                    $estHc = $codeCategorie === $classe['hc'];
                    $categorie = CategorieClassification::withoutGlobalScopes()->updateOrCreate(
                        ['referentiel_id' => $referentiel->id, 'code' => $codeCategorie],
                        ['libelle' => $estHc ? 'Hors catégorie — '.$classe['libelle'] : $codeCategorie, 'ordre' => (++$ordreCategorie) * 10, 'actif' => true, 'etat' => 1],
                    );

                    // Échelons de la catégorie, chaînés A → B → C (pas de position suivante après le dernier).
                    $montants = $estHc ? ['HC' => null] : self::BAREME[$codeCategorie];
                    $suivante = null;
                    foreach (array_reverse($montants, true) as $codeEchelon => $montant) {
                        $suivante = PositionClassification::withoutGlobalScopes()->updateOrCreate(
                            ['referentiel_id' => $referentiel->id, 'code' => $codeCategorie.'-'.$codeEchelon],
                            [
                                'categorie_id' => $categorie->id,
                                'classe_id' => $modeleClasse->id,
                                'echelon_id' => $echelons[$codeEchelon]->id,
                                'salaire_minimum' => $montant,
                                'ordre' => $categorie->ordre * 10 + $echelons[$codeEchelon]->ordre,
                                'debut_effet' => self::DATE_EFFET,
                                'position_suivante_id' => $suivante?->id,
                                'actif' => true,
                                'statut' => 'active',
                                'etat' => 1,
                            ],
                        );
                        $positions++;
                    }
                }
            }

            return ['referentiel' => $referentiel, 'positions' => $positions];
        });
    }
}
