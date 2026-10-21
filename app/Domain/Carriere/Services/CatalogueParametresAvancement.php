<?php

namespace App\Domain\Carriere\Services;

/**
 * Catalogue des paramètres modifiables des avancements et de l'ancienneté. La valeur par défaut
 * vient de config/avancement.php. La clé « reference » documente le texte (non affichée).
 */
class CatalogueParametresAvancement
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function definitions(): array
    {
        return [
            // --- Avancement d'échelon ---
            'delai_echelon_mois' => [
                'groupe' => 'Avancement d\'échelon',
                'libelle' => 'Ancienneté maximale dans un échelon (mois)',
                'type' => 'entier', 'min' => 1,
                'reference' => 'CCIT art. 33',
            ],
            'avancement_echelon_mode' => [
                'groupe' => 'Avancement d\'échelon',
                'libelle' => 'Nature de l\'échéance d\'échelon',
                'type' => 'choix',
                'options' => [
                    'droit' => 'Avancement dû à l\'échéance (rappel en cas de retard)',
                    'examen' => 'Échéance d\'examen (l\'employeur peut différer avec motif)',
                ],
                'reference' => 'CCIT art. 33',
            ],
            'periodes_avancement' => [
                'groupe' => 'Avancement d\'échelon',
                'libelle' => 'Périodes déduites de l\'ancienneté d\'échelon',
                'type' => 'choix',
                'options' => [
                    'idem_prime' => 'Les mêmes que pour l\'ancienneté (prime)',
                    'aucune_exclusion' => 'Aucune : ancienneté d\'échelon brute',
                ],
                'reference' => 'CCIT art. 10, 40',
            ],

            // --- Avancement de catégorie et de classe ---
            'fenetre_categorie_attente_mois' => [
                'groupe' => 'Avancement de catégorie et de classe',
                'libelle' => 'Ancienneté dans le dernier échelon avant la fenêtre (mois)',
                'type' => 'entier', 'min' => 0,
                'reference' => 'CCIT art. 33',
            ],
            'fenetre_categorie_ouverture_mois' => [
                'groupe' => 'Avancement de catégorie et de classe',
                'libelle' => 'Ouverture de la fenêtre après cette ancienneté (mois)',
                'type' => 'entier', 'min' => 0,
                'reference' => 'CCIT art. 33',
            ],
            'fenetre_categorie_limite_mois' => [
                'groupe' => 'Avancement de catégorie et de classe',
                'libelle' => 'Limite de la fenêtre après cette ancienneté (mois)',
                'type' => 'entier', 'min' => 1,
                'reference' => 'CCIT art. 33',
            ],
            'echelon_arrivee' => [
                'groupe' => 'Avancement de catégorie et de classe',
                'libelle' => 'Échelon d\'arrivée dans la nouvelle catégorie',
                'type' => 'choix',
                'options' => [
                    'echelon_a' => 'Échelon A (salaire maintenu s\'il est plus élevé)',
                    'salaire_immediatement_superieur' => 'Premier échelon au moins égal au salaire actuel',
                ],
                'reference' => 'CCIT art. 33 (muet)',
            ],
            'reinitialiser_anciennete_echelon' => [
                'groupe' => 'Avancement de catégorie et de classe',
                'libelle' => 'L\'ancienneté d\'échelon repart de zéro après un changement de catégorie ou de classe',
                'type' => 'booleen',
                'reference' => 'Non tranché',
            ],
            'categorie_accueil_changement_classe' => [
                'groupe' => 'Avancement de catégorie et de classe',
                'libelle' => 'Catégorie d\'accueil lors d\'un changement de classe',
                'type' => 'choix',
                'options' => [
                    'premiere_categorie' => 'Première catégorie de la nouvelle classe',
                    'selon_diplome' => 'Catégorie correspondant au diplôme ou à la qualification',
                ],
                'reference' => 'Annexe I',
            ],
            'avancement_pendant_essai_promotion' => [
                'groupe' => 'Avancement de catégorie et de classe',
                'libelle' => 'Ancienneté d\'échelon pendant l\'essai d\'une promotion',
                'type' => 'choix',
                'options' => [
                    'continue' => 'Continue de courir dans l\'ancienne position',
                    'suspendue' => 'Suspendue jusqu\'à la fin de l\'essai',
                ],
                'reference' => 'CCIT art. 29 (muet)',
            ],

            // --- Ancienneté ---
            'codes_maladie_anciennete' => [
                'groupe' => 'Ancienneté',
                'libelle' => 'Types de congé maladie (comptés 6 mois, puis jusqu\'au remplacement)',
                'type' => 'types_conges',
                'reference' => 'CCIT art. 40',
            ],
            'maladie_duree_comptee_mois' => [
                'groupe' => 'Ancienneté',
                'libelle' => 'Durée de maladie toujours comptée (mois)',
                'type' => 'entier', 'min' => 0,
                'reference' => 'CCIT art. 40',
            ],
            'codes_absences_personnelles' => [
                'groupe' => 'Ancienneté',
                'libelle' => 'Types d\'absence pour raisons personnelles',
                'type' => 'types_conges',
                'reference' => 'CCIT art. 40',
            ],
            'absences_personnelles_limite_jours' => [
                'groupe' => 'Ancienneté',
                'libelle' => 'Absences personnelles comptées dans la limite de (jours)',
                'type' => 'entier', 'min' => 0,
                'reference' => 'CCIT art. 40',
            ],
            'absences_personnelles_periode' => [
                'groupe' => 'Ancienneté',
                'libelle' => 'Période de cette limite',
                'type' => 'choix',
                'options' => ['annee' => 'Par année civile', 'carriere' => 'Sur toute la carrière'],
                'reference' => 'Non tranché',
            ],

            // --- Prime d'ancienneté ---
            'prime_mois_seuil' => [
                'groupe' => 'Prime d\'ancienneté',
                'libelle' => 'Mois où un nouveau taux est atteint',
                'type' => 'choix',
                'options' => [
                    'taux_fin_de_mois' => 'Taux atteint en fin de mois appliqué au mois entier',
                    'prorata_jours' => 'Prorata des jours avant et après le seuil',
                ],
                'reference' => 'Non tranché',
            ],
            'prime_base_pendant_interim' => [
                'groupe' => 'Prime d\'ancienneté',
                'libelle' => 'Base de la prime pendant un intérim',
                'type' => 'choix',
                'options' => [
                    'categorie_origine' => 'Catégorie d\'origine du salarié',
                    'categorie_occupee' => 'Catégorie occupée par intérim',
                ],
                'reference' => 'CCIT art. 13',
            ],
            'echelon_reference_prime' => [
                'groupe' => 'Prime d\'ancienneté',
                'libelle' => 'Échelon de référence du minimum de la catégorie',
                'type' => 'texte',
                'reference' => 'CCIT art. 40',
            ],
            'arrondi_montants' => [
                'groupe' => 'Prime d\'ancienneté',
                'libelle' => 'Arrondi des montants (FCFA)',
                'type' => 'choix',
                'options' => ['1' => 'Au franc', '5' => 'Aux 5 FCFA', '10' => 'Aux 10 FCFA'],
                'reference' => 'Non tranché',
            ],

            // --- Distinctions honorifiques ---
            'distinction_35_ans_mois' => [
                'groupe' => 'Distinctions honorifiques',
                'libelle' => 'Gratification à 35 ans (mois)',
                'type' => 'decimal', 'min' => 4,
                'reference' => 'CCIT art. 64',
            ],
            'distinction_base' => [
                'groupe' => 'Distinctions honorifiques',
                'libelle' => 'Base de la gratification (plus la prime d\'ancienneté)',
                'type' => 'choix',
                'options' => ['salaire_reel' => 'Salaire de base réel', 'minimum_categorie' => 'Minimum de la catégorie'],
                'reference' => 'CCIT art. 64',
            ],
            'distinction_anciennete' => [
                'groupe' => 'Distinctions honorifiques',
                'libelle' => 'Ancienneté retenue',
                'type' => 'choix',
                'options' => ['utile' => 'Ancienneté utile (périodes déduites)', 'brute' => 'Ancienneté brute depuis l\'embauche'],
                'reference' => 'CCIT art. 64',
            ],
        ];
    }
}
