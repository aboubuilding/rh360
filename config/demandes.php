<?php

/*
|--------------------------------------------------------------------------
| Demandes et autorisations — valeurs par défaut
|--------------------------------------------------------------------------
|
| Points que le Code du travail (loi n°2021-012) et la CCIT 2026 ne tranchent pas, avec la
| lecture retenue par défaut (la plus favorable au salarié quand les textes divergent).
| Chaque entreprise peut les modifier depuis l'écran « Paramètres des demandes »
| (table parametres_demandes).
| Les valeurs propres à un type de demande (délais, justificatif, plafond, ancienneté
| minimale) sont portées par le type lui-même (Congés › Types de congés), et les durées
| des permissions par événement par la table evenements_permission.
|
*/

return [

    // --- Calendrier et calculs ---
    // Jours ouvrés de l'entreprise, pour les délais exprimés en jours ouvrés (CCIT art. 6).
    'jours_ouvres' => 'lun_ven',
    // Diviseur du salaire mensuel pour un salaire journalier (maladie, maternité, sans solde).
    'salaire_journalier_diviseur' => 30,

    // --- Congé payé (Code art. 200 à 203 ; CCIT art. 49) ---
    // Droit acquis après 1 an de service effectif ; par anticipation au prorata après 6 mois.
    'conge_anciennete_droit_mois' => 12,
    'conge_anciennete_anticipation_mois' => 6,
    // Arrondi des jours acquis : demi-journée supérieure ou au centième.
    'conge_arrondi_acquisition' => 'demi_jour_superieur',
    // « 30 jours calendaires » : les jours fériés compris dans le congé ne sont pas décomptés.
    'conge_decompte_feries' => false,
    // Le mois de congé payé compte pour l'acquisition (Code art. 65 lu à la lettre l'exclut).
    'conge_acquisition_pendant_conge' => true,
    // Ordre de départ communiqué au moins 15 jours avant (CCIT art. 49 b).
    'conge_delai_ordre_depart_jours' => 15,
    'conge_ordre_depart_tardif' => 'bloquer',
    // Date fixée : ni avancée ni retardée de plus de 3 mois, sauf force majeure (CCIT art. 49 b).
    'conge_decalage_max_mois' => 3,
    // Durée de service ouvrant droit au congé limitée à 2 ans (Code art. 201).
    'conge_report_max_annees' => 2,
    // Reliquat d'un lot au-delà de cette limite : alerte et départ imposé, ou perte.
    'conge_lot_expire' => 'alerte_depart_impose',
    // Allocation de congé : rubriques exclues de la rémunération de référence (primes de
    // rendement et remboursements de frais, Code art. 202 ; CCIT art. 49 c).
    'conge_allocation_rubriques_exclues' => 'PRIME-REND,PRIME-TRANSP',
    // L'allocation de congé n'est jamais inférieure au maintien du salaire des jours de congé
    // (Code art. 202 : « sauf dispositions plus favorables »).
    'conge_comparaison_maintien' => true,

    // --- Permissions exceptionnelles (CCIT art. 50 ; Code art. 200) ---
    // Plafond annuel des permissions non déductibles : aucun selon la CCIT (le Code en fixe 10).
    'permissions_plafond_annuel' => null,
    // Pièce non remise dans le délai : absence non justifiée, ou jours déduits du congé.
    'permissions_justificatif_tardif' => 'absence_non_justifiee',
    // Permission de naissance d'une mère déjà en congé de maternité : absorbée ou cumulée.
    'naissance_mere_en_maternite' => 'absorbee',

    // --- Enfant malade et veuvage (CCIT art. 51) ---
    // Enfant malade : enfant de 21 ans révolus au plus, à la charge permanente du salarié.
    'enfant_malade_age_max' => 21,
    'veuvage_duree_mois' => 1,
    // Le mois de veuvage s'ajoute à la permission de 4 jours pour décès du conjoint.
    'veuvage_cumul_permission' => true,
    // Prime d'assistance : au moins 1 mois de salaire de base.
    'veuvage_prime_mois' => 1.0,
    'veuvage_prime_base' => 'salaire_base',

    // --- Maternité et allaitement (Code art. 190, 191 ; CCIT art. 48, 49 d) ---
    'maternite_semaines' => 14,
    'maternite_semaines_apres' => 6,
    'maternite_prolongation_semaines' => 3,
    // Les semaines de prolongation s'ajoutent après la naissance.
    'maternite_prolongation_placement' => 'apres',
    // Indemnité « équivalente à son salaire » (CCIT), plus favorable que le partage du Code.
    'maternite_maintien_integral' => true,
    // L'employeur avance la part de la CNSS et la réclame ensuite.
    'maternite_prepaiement' => true,
    'allaitement_duree_mois' => 15,
    'allaitement_heures_jour' => 1.0,
    'allaitement_remunere' => true,

    // --- Maladie (CCIT art. 14, 16 ; Code art. 69) ---
    // Constat par le service médical de l'entreprise dans les 48 heures : aucune autre formalité.
    'maladie_delai_service_medical_heures' => 48,
    // Notification après 72 heures sans force majeure : indemnisation à partir de la notification.
    'maladie_notification_tardive' => 'depuis_notification',
    'maladie_duree_max_mois' => 6,

    // --- Congé sans solde (CCIT art. 15) ---
    'css_duree_max_mois' => 12,
    'css_renouvellements' => 1,
    // Le congé sans solde ne compte pas pour l'acquisition du congé payé.
    'css_compte_acquisition' => false,

    // --- Activités syndicales (CCIT art. 6, 59) ---
    // Crédit d'heures confédéral « ni pris en continu » : maximum d'heures par jour.
    'syndical_credit_heures_max_jour' => 8.0,
    // Réintégration après un mandat : au plus tard 2 mois après la demande.
    'syndical_reintegration_mois' => 2,

    // --- Heures supplémentaires et jours fériés (CCIT art. 36, 45, 47 ; Code art. 181) ---
    'hs_max_semaine' => 20,
    'hs_max_jour' => 10,
    'hs_max_jour_urgence' => 12,
    // Heures relevées sans ordre de l'employeur : à arbitrer par les RH, ou refusées.
    'hs_sans_ordre' => 'a_arbitrer',
    // Jour férié non payé en cas d'absence la veille et le lendemain : ouvriers seulement, ou tous.
    'jours_feries_regle_portee' => 'ouvriers',

    // --- Missions (CCIT art. 38) ---
    // Taux horaire de base de la catégorie : minimum de la catégorie, ou salaire réel.
    'mission_taux_base' => 'minimum_categorie',
];
