<?php

namespace App\Domain\Interim\Services;

/**
 * Catalogue des paramètres modifiables de la gestion des intérims : libellé, type,
 * référence au texte, et choix possibles. La valeur par défaut vient de config/interim.php.
 */
class CatalogueParametresInterim
{
    public const NON_TRANCHE = 'Non tranché par les textes';

    /**
     * @return array<string, array{libelle:string, type:string, reference:string, groupe:string, options?:array<string,string>, min?:int}>
     */
    public static function definitions(): array
    {
        return [
            // --- Intérim interne ---
            'duree_max_interim_mois' => [
                'groupe' => 'Intérim interne',
                'libelle' => 'Durée maximale sans motif éligible (mois)',
                'type' => 'entier', 'min' => 1,
                'reference' => 'CCIT, art. 13',
            ],
            'mode_calcul_duree' => [
                'groupe' => 'Intérim interne',
                'libelle' => 'Mode de calcul des durées',
                'type' => 'choix',
                'options' => ['date_a_date' => 'Date à date (calendaire)'],
                'reference' => self::NON_TRANCHE,
            ],
            'cumul_interims_meme_poste' => [
                'groupe' => 'Intérim interne',
                'libelle' => 'Cumuler les intérims successifs sur un même poste et un même titulaire',
                'type' => 'booleen',
                'reference' => self::NON_TRANCHE,
            ],
            'delai_maladie_mois' => [
                'groupe' => 'Intérim interne',
                'libelle' => 'Délai légal — maladie ou accident non professionnel du titulaire (mois)',
                'type' => 'entier', 'min' => 1,
                'reference' => 'Code du travail, art. 65 c ; CCIT, art. 14',
            ],
            'delai_detention_preventive_mois' => [
                'groupe' => 'Intérim interne',
                'libelle' => 'Délai légal — détention préventive du titulaire (mois)',
                'type' => 'entier', 'min' => 1,
                'reference' => 'Code du travail, art. 65 h',
            ],
            'origine_delai_legal' => [
                'groupe' => 'Intérim interne',
                'libelle' => 'Point de départ des délais légaux en mois',
                'type' => 'choix',
                'options' => [
                    'debut_absence_titulaire' => 'Début de l\'absence du titulaire (suspension de son contrat)',
                    'debut_interim' => 'Début de l\'intérim',
                ],
                'reference' => 'Code du travail, art. 65 (suspension du contrat du titulaire)',
            ],

            // --- Paie ---
            'prorata_indemnite' => [
                'groupe' => 'Paie',
                'libelle' => 'Proratisation de l\'indemnité sur un mois incomplet',
                'type' => 'choix',
                'options' => [
                    'jours_reels_du_mois' => 'Jours réels du mois',
                    'base_30' => 'Base 30 jours',
                ],
                'reference' => self::NON_TRANCHE,
            ],
            'bloquer_cloture_paie_si_a_regulariser' => [
                'groupe' => 'Paie',
                'libelle' => 'Bloquer la validation de la paie tant qu\'un intérim est à régulariser',
                'type' => 'booleen',
                'reference' => self::NON_TRANCHE,
            ],
            'referentiel_bareme_id' => [
                'groupe' => 'Paie',
                'libelle' => 'Référentiel du barème des salaires minimums (Annexe II CCIT 2026)',
                'type' => 'referentiel',
                'reference' => 'CCIT 2026, Annexe II',
            ],
            'referentiels_conventions_ids' => [
                'groupe' => 'Paie',
                'libelle' => 'Conventions sectorielles applicables (priment si plus favorables)',
                'type' => 'referentiels',
                'reference' => 'Convention de branche applicable',
            ],
            'echelon_reference' => [
                'groupe' => 'Paie',
                'libelle' => 'Échelon de référence du minimum de la catégorie occupée',
                'type' => 'texte',
                'reference' => 'Règle RG-INT-06',
            ],

            // --- Alertes ---
            'alerte_jours_avant_fin' => [
                'groupe' => 'Alertes',
                'libelle' => 'Alerte avant l\'échéance de l\'intérim (jours)',
                'type' => 'entier', 'min' => 1,
                'reference' => self::NON_TRANCHE,
            ],
            'alerte_maladie_titulaire_mois' => [
                'groupe' => 'Alertes',
                'libelle' => 'Alerte d\'absence maladie du titulaire (mois)',
                'type' => 'entier', 'min' => 1,
                'reference' => 'Code du travail, art. 65 c ; CCIT, art. 14 et 16',
            ],
            'codes_conges_maladie' => [
                'groupe' => 'Alertes',
                'libelle' => 'Types de congé correspondant à une maladie du titulaire',
                'type' => 'types_conges',
                'reference' => 'Code du travail, art. 65 c',
            ],

            // --- Travail intérimaire ---
            'missions_duree_max_mois' => [
                'groupe' => 'Travail intérimaire',
                'libelle' => 'Durée maximale d\'une mission (mois, vide = non limitée)',
                'type' => 'entier_facultatif', 'min' => 1,
                'reference' => 'Décret n°2022-027/PR (Code, art. 63) — non intégré',
            ],
            'codes_absences_greve' => [
                'groupe' => 'Travail intérimaire',
                'libelle' => 'Types d\'absence correspondant à une grève',
                'type' => 'types_conges',
                'reference' => 'Code du travail, art. 62',
            ],
            'alerte_sst_jours_avant_mission' => [
                'groupe' => 'Travail intérimaire',
                'libelle' => 'Alerte si la checklist SST n\'est pas validée avant le début d\'une mission (jours)',
                'type' => 'entier', 'min' => 1,
                'reference' => self::NON_TRANCHE,
            ],
            'alerte_pieces_pret_jours' => [
                'groupe' => 'Prêt de main-d\'œuvre',
                'libelle' => 'Alerte si les trois pièces ne sont pas réunies avant le début du prêt (jours)',
                'type' => 'entier', 'min' => 1,
                'reference' => self::NON_TRANCHE,
            ],
            'missions_renouvellements_max' => [
                'groupe' => 'Travail intérimaire',
                'libelle' => 'Nombre maximal de renouvellements d\'une mission (vide = non limité)',
                'type' => 'entier_facultatif', 'min' => 0,
                'reference' => 'Décret n°2022-027/PR (Code, art. 63) — non intégré',
            ],
        ];
    }

    public static function existe(string $cle): bool
    {
        return array_key_exists($cle, self::definitions());
    }
}
