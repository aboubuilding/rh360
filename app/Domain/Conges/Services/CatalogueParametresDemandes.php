<?php

namespace App\Domain\Conges\Services;

/**
 * Catalogue des paramètres modifiables des demandes et autorisations. La valeur par défaut
 * vient de config/demandes.php. La clé « reference » documente le texte (non affichée).
 */
class CatalogueParametresDemandes
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function definitions(): array
    {
        return [
            // --- Calendrier et calculs ---
            'jours_ouvres' => [
                'groupe' => 'Calendrier et calculs',
                'libelle' => 'Jours ouvrés de l\'entreprise',
                'type' => 'choix',
                'options' => ['lun_ven' => 'Du lundi au vendredi', 'lun_sam' => 'Du lundi au samedi'],
                'reference' => 'CCIT art. 6',
            ],
            'salaire_journalier_diviseur' => [
                'groupe' => 'Calendrier et calculs',
                'libelle' => 'Diviseur du salaire mensuel pour un salaire journalier',
                'type' => 'entier', 'min' => 1,
                'reference' => 'Non fixé par les textes',
            ],

            // --- Congé payé ---
            'conge_anciennete_droit_mois' => [
                'groupe' => 'Congé payé',
                'libelle' => 'Service effectif ouvrant le droit au congé (mois)',
                'type' => 'entier', 'min' => 1, 'max' => 12,
                'reference' => 'Code art. 201',
            ],
            'conge_anciennete_anticipation_mois' => [
                'groupe' => 'Congé payé',
                'libelle' => 'Service minimum pour un congé par anticipation, avec accord (mois)',
                'type' => 'entier', 'min' => 1, 'max' => 6,
                'reference' => 'Code art. 201',
            ],
            'conge_arrondi_acquisition' => [
                'groupe' => 'Congé payé',
                'libelle' => 'Arrondi des jours acquis',
                'type' => 'choix',
                'options' => ['demi_jour_superieur' => 'À la demi-journée supérieure', 'centieme' => 'Au centième de jour'],
                'reference' => 'Non fixé par les textes',
            ],
            'conge_decompte_feries' => [
                'groupe' => 'Congé payé',
                'libelle' => 'Décompter les jours fériés compris dans le congé',
                'type' => 'booleen',
                'reference' => 'CCIT art. 49 a (jours calendaires)',
            ],
            'conge_acquisition_pendant_conge' => [
                'groupe' => 'Congé payé',
                'libelle' => 'Le temps de congé payé compte pour l\'acquisition du congé suivant',
                'type' => 'booleen',
                'reference' => 'Code art. 65, 200',
            ],
            'conge_delai_ordre_depart_jours' => [
                'groupe' => 'Congé payé',
                'libelle' => 'Délai minimum de communication de l\'ordre de départ (jours)',
                'type' => 'entier', 'min' => 15,
                'reference' => 'CCIT art. 49 b',
            ],
            'conge_ordre_depart_tardif' => [
                'groupe' => 'Congé payé',
                'libelle' => 'Ordre de départ communiqué trop tard',
                'type' => 'choix',
                'options' => ['bloquer' => 'Refuser le départ à cette date', 'alerter' => 'Alerter seulement'],
                'reference' => 'CCIT art. 49 b',
            ],
            'conge_decalage_max_mois' => [
                'groupe' => 'Congé payé',
                'libelle' => 'Décalage maximum d\'une date fixée, hors force majeure (mois)',
                'type' => 'entier', 'min' => 0, 'max' => 3,
                'reference' => 'CCIT art. 49 b',
            ],
            'conge_report_max_annees' => [
                'groupe' => 'Congé payé',
                'libelle' => 'Durée maximale de service ouvrant droit au congé (années)',
                'type' => 'entier', 'min' => 1, 'max' => 2,
                'reference' => 'Code art. 201',
            ],
            'conge_lot_expire' => [
                'groupe' => 'Congé payé',
                'libelle' => 'Droits non pris au-delà de cette durée',
                'type' => 'choix',
                'options' => [
                    'alerte_depart_impose' => 'Alerter et imposer la date de départ',
                    'perte' => 'Perte des jours restants',
                ],
                'reference' => 'Code art. 201 (muet sur la sanction)',
            ],

            'conge_allocation_rubriques_exclues' => [
                'groupe' => 'Congé payé',
                'libelle' => 'Rubriques exclues de la base de l\'allocation de congé (primes de rendement, remboursements de frais), codes séparés par des virgules',
                'type' => 'texte',
                'reference' => 'Code art. 202 ; CCIT art. 49 c',
            ],

            // --- Permissions exceptionnelles ---
            'permissions_plafond_annuel' => [
                'groupe' => 'Permissions exceptionnelles',
                'libelle' => 'Plafond annuel des permissions non déductibles du congé (jours)',
                'type' => 'entier_facultatif', 'min' => 1,
                'reference' => 'CCIT art. 50 ; Code art. 200',
            ],
            'permissions_justificatif_tardif' => [
                'groupe' => 'Permissions exceptionnelles',
                'libelle' => 'Justificatif non remis dans le délai',
                'type' => 'choix',
                'options' => [
                    'absence_non_justifiee' => 'L\'absence devient non justifiée',
                    'deduction_conge' => 'Les jours sont déduits du congé payé',
                ],
                'reference' => 'CCIT art. 50',
            ],
            'naissance_mere_en_maternite' => [
                'groupe' => 'Permissions exceptionnelles',
                'libelle' => 'Permission de naissance d\'une mère en congé de maternité',
                'type' => 'choix',
                'options' => ['absorbee' => 'Comprise dans le congé de maternité', 'cumulee' => 'Ajoutée au congé de maternité'],
                'reference' => 'CCIT art. 50 (muet)',
            ],

            // --- Enfant malade et veuvage ---
            'enfant_malade_age_max' => [
                'groupe' => 'Enfant malade et veuvage',
                'libelle' => 'Âge maximum de l\'enfant malade (années révolues)',
                'type' => 'entier', 'min' => 21,
                'reference' => 'CCIT art. 51',
            ],
            'veuvage_duree_mois' => [
                'groupe' => 'Enfant malade et veuvage',
                'libelle' => 'Durée de l\'absence pour veuvage ou décès d\'un descendant (mois)',
                'type' => 'entier', 'min' => 1,
                'reference' => 'CCIT art. 51',
            ],
            'veuvage_cumul_permission' => [
                'groupe' => 'Enfant malade et veuvage',
                'libelle' => 'Cette absence s\'ajoute à la permission pour décès',
                'type' => 'booleen',
                'reference' => 'CCIT art. 50, 51',
            ],
            'veuvage_prime_mois' => [
                'groupe' => 'Enfant malade et veuvage',
                'libelle' => 'Prime d\'assistance (mois de salaire)',
                'type' => 'decimal', 'min' => 1,
                'reference' => 'CCIT art. 51',
            ],
            'veuvage_prime_base' => [
                'groupe' => 'Enfant malade et veuvage',
                'libelle' => 'Base de la prime d\'assistance',
                'type' => 'choix',
                'options' => [
                    'salaire_base' => 'Salaire de base',
                    'salaire_base_primes_fixes' => 'Salaire de base et primes fixes',
                ],
                'reference' => 'CCIT art. 51',
            ],

            // --- Maternité et allaitement ---
            'maternite_semaines' => [
                'groupe' => 'Maternité et allaitement',
                'libelle' => 'Durée du congé de maternité (semaines)',
                'type' => 'entier', 'min' => 14,
                'reference' => 'Code art. 190',
            ],
            'maternite_semaines_apres' => [
                'groupe' => 'Maternité et allaitement',
                'libelle' => 'Dont semaines après l\'accouchement',
                'type' => 'entier', 'min' => 6,
                'reference' => 'Code art. 190',
            ],
            'maternite_prolongation_semaines' => [
                'groupe' => 'Maternité et allaitement',
                'libelle' => 'Prolongation pour maladie, grossesse multiple ou santé de l\'enfant (semaines)',
                'type' => 'entier', 'min' => 3,
                'reference' => 'Code art. 190',
            ],
            'maternite_prolongation_placement' => [
                'groupe' => 'Maternité et allaitement',
                'libelle' => 'Placement de la prolongation',
                'type' => 'choix',
                'options' => ['apres' => 'Après l\'accouchement', 'avant' => 'Avant l\'accouchement'],
                'reference' => 'Code art. 190 (muet)',
            ],
            'maternite_maintien_integral' => [
                'groupe' => 'Maternité et allaitement',
                'libelle' => 'Indemnité égale au salaire (l\'employeur complète la part de la CNSS)',
                'type' => 'booleen',
                'reference' => 'CCIT art. 49 d ; Code art. 190',
            ],
            'maternite_prepaiement' => [
                'groupe' => 'Maternité et allaitement',
                'libelle' => 'L\'employeur avance la part de la CNSS',
                'type' => 'booleen',
                'reference' => 'CCIT art. 49 d',
            ],
            'allaitement_duree_mois' => [
                'groupe' => 'Maternité et allaitement',
                'libelle' => 'Période d\'allaitement après la naissance (mois)',
                'type' => 'entier', 'min' => 15,
                'reference' => 'Code art. 191',
            ],
            'allaitement_heures_jour' => [
                'groupe' => 'Maternité et allaitement',
                'libelle' => 'Repos pour allaitement par jour de travail (heures)',
                'type' => 'decimal', 'min' => 1,
                'reference' => 'Code art. 191',
            ],
            'allaitement_remunere' => [
                'groupe' => 'Maternité et allaitement',
                'libelle' => 'Le repos pour allaitement est payé',
                'type' => 'booleen',
                'reference' => 'Code art. 191 (muet)',
            ],

            // --- Maladie ---
            'maladie_delai_service_medical_heures' => [
                'groupe' => 'Maladie',
                'libelle' => 'Constat par le service médical de l\'entreprise, dispensant des autres formalités (heures)',
                'type' => 'entier', 'min' => 48,
                'reference' => 'CCIT art. 14',
            ],
            'maladie_notification_tardive' => [
                'groupe' => 'Maladie',
                'libelle' => 'Maladie notifiée après le délai, sans force majeure',
                'type' => 'choix',
                'options' => [
                    'depuis_notification' => 'Indemnisée à partir de la notification',
                    'aucune_indemnite' => 'Aucune indemnisation pour l\'arrêt',
                ],
                'reference' => 'CCIT art. 16',
            ],
            'maladie_duree_max_mois' => [
                'groupe' => 'Maladie',
                'libelle' => 'Durée d\'une période de maladie avant rupture possible (mois)',
                'type' => 'entier', 'min' => 6,
                'reference' => 'CCIT art. 14, 16 ; Code art. 65',
            ],

            // --- Congé sans solde ---
            'css_duree_max_mois' => [
                'groupe' => 'Congé sans solde',
                'libelle' => 'Durée maximale d\'un congé sans solde (mois)',
                'type' => 'entier', 'min' => 1,
                'reference' => 'CCIT art. 15',
            ],
            'css_renouvellements' => [
                'groupe' => 'Congé sans solde',
                'libelle' => 'Nombre de renouvellements possibles',
                'type' => 'entier', 'min' => 0,
                'reference' => 'CCIT art. 15',
            ],
            'css_compte_acquisition' => [
                'groupe' => 'Congé sans solde',
                'libelle' => 'Le congé sans solde compte pour l\'acquisition du congé payé',
                'type' => 'booleen',
                'reference' => 'Code art. 65, 200 d',
            ],

            // --- Activités syndicales ---
            'syndical_credit_heures_max_jour' => [
                'groupe' => 'Activités syndicales et représentation',
                'libelle' => 'Crédit d\'heures des responsables confédéraux : maximum par jour (heures)',
                'type' => 'decimal', 'min' => 1,
                'reference' => 'CCIT art. 6 §7 (ni pris en continu)',
            ],
            'syndical_reintegration_mois' => [
                'groupe' => 'Activités syndicales et représentation',
                'libelle' => 'Délai maximum de réintégration après un mandat syndical (mois)',
                'type' => 'entier', 'min' => 0, 'max' => 2,
                'reference' => 'CCIT art. 6 §8',
            ],

            // --- Heures supplémentaires et jours fériés ---
            'hs_max_semaine' => [
                'groupe' => 'Heures supplémentaires et jours fériés',
                'libelle' => 'Heures supplémentaires par semaine (maximum)',
                'type' => 'entier', 'min' => 0, 'max' => 20,
                'reference' => 'Code art. 181',
            ],
            'hs_max_jour' => [
                'groupe' => 'Heures supplémentaires et jours fériés',
                'libelle' => 'Durée du travail par jour (maximum, heures)',
                'type' => 'entier', 'min' => 1, 'max' => 10,
                'reference' => 'Code art. 181',
            ],
            'hs_max_jour_urgence' => [
                'groupe' => 'Heures supplémentaires et jours fériés',
                'libelle' => 'Durée du travail par jour pour des travaux urgents (maximum, heures)',
                'type' => 'entier', 'min' => 1, 'max' => 12,
                'reference' => 'Code art. 181',
            ],
            'hs_sans_ordre' => [
                'groupe' => 'Heures supplémentaires et jours fériés',
                'libelle' => 'Heures relevées sans demande de l\'employeur',
                'type' => 'choix',
                'options' => ['a_arbitrer' => 'À arbitrer par les RH avant la paie', 'refuser' => 'Refusées'],
                'reference' => 'CCIT art. 36',
            ],
            'jours_feries_regle_portee' => [
                'groupe' => 'Heures supplémentaires et jours fériés',
                'libelle' => 'Jour férié non payé après une absence la veille et le lendemain : salariés concernés',
                'type' => 'choix',
                'options' => ['ouvriers' => 'Les ouvriers', 'tous' => 'Tous les salariés'],
                'reference' => 'CCIT art. 47',
            ],

            // --- Missions ---
            'mission_taux_base' => [
                'groupe' => 'Missions',
                'libelle' => 'Taux horaire servant au calcul des indemnités de mission',
                'type' => 'choix',
                'options' => [
                    'minimum_categorie' => 'Salaire minimum de la catégorie',
                    'salaire_reel' => 'Salaire de base du salarié',
                ],
                'reference' => 'CCIT art. 38',
            ],
        ];
    }
}
