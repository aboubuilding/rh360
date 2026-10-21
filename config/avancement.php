<?php

/*
|--------------------------------------------------------------------------
| Avancements et ancienneté — valeurs par défaut
|--------------------------------------------------------------------------
|
| Points que le Code du travail (loi n°2021-012) et la CCIT 2026 ne tranchent pas,
| avec la lecture retenue par défaut. Chaque entreprise peut les modifier depuis
| l'écran « Paramètres des avancements » (table parametres_avancement).
| Le taux et la base de la prime d'ancienneté sont dans la règle d'ancienneté
| versionnée (Paie › Paramètres).
|
*/

return [

    // --- Avancement d'échelon (CCIT art. 33) ---
    // Ancienneté maximale dans l'échelon, utilisée faute de règle d'évolution du référentiel.
    'delai_echelon_mois' => 24,
    // « droit » : avancement dû à l'échéance, avec rappel en cas de retard ; « examen » : délai d'examen.
    'avancement_echelon_mode' => 'droit',
    // Périodes déduites de l'ancienneté d'échelon : celles de l'ancienneté utile (« idem_prime ») ou aucune.
    'periodes_avancement' => 'idem_prime',

    // Toute modification d'un élément du contrat est notifiée par écrit avant sa prise d'effet
    // (CCIT art. 12 ; Code art. 71) : la prise d'effet d'un acte non notifié est refusée.
    'exiger_notification_prealable' => true,

    // --- Avancement de catégorie (CCIT art. 33) ---
    // Fenêtre : au moins 1 an et au plus 2 ans après 2 ans d'ancienneté dans le dernier échelon.
    'fenetre_categorie_attente_mois' => 24,
    'fenetre_categorie_ouverture_mois' => 12,
    'fenetre_categorie_limite_mois' => 24,
    // Échelon d'arrivée : « echelon_a » ou « salaire_immediatement_superieur » (jamais de baisse de salaire).
    'echelon_arrivee' => 'echelon_a',
    // L'ancienneté d'échelon repart de zéro après un avancement de catégorie ou de classe.
    'reinitialiser_anciennete_echelon' => true,
    // Catégorie d'accueil lors d'un changement de classe : « premiere_categorie » ou « selon_diplome ».
    'categorie_accueil_changement_classe' => 'premiere_categorie',
    // Pendant l'essai d'une promotion, l'ancienneté d'échelon de l'ancienne position « continue » ou est « suspendue ».
    'avancement_pendant_essai_promotion' => 'continue',

    // --- Ancienneté utile (CCIT art. 40 ; Code art. 46, 65) ---
    // Les absences dont le type de congé a un impact « suspendue » sur l'ancienneté sont déduites.
    // Maladie : comptée 6 mois, puis jusqu'au remplacement du salarié.
    'codes_maladie_anciennete' => ['MAL'],
    'maladie_duree_comptee_mois' => 6,
    // Absences pour raisons personnelles comptées dans la limite de N jours par période.
    'codes_absences_personnelles' => [],
    'absences_personnelles_limite_jours' => 10,
    'absences_personnelles_periode' => 'annee',

    // --- Prime d'ancienneté (CCIT art. 40) ---
    // Mois où un seuil est atteint : taux atteint en fin de mois appliqué au mois entier, ou prorata des jours.
    'prime_mois_seuil' => 'taux_fin_de_mois',
    // Pendant un intérim : base de la catégorie d'origine ou de la catégorie occupée.
    'prime_base_pendant_interim' => 'categorie_origine',
    // Échelon dont le minimum sert de base « minimum de la catégorie ».
    'echelon_reference_prime' => 'A',
    // Arrondi des montants (FCFA) : 1, 5 ou 10.
    'arrondi_montants' => '1',

    // --- Distinctions honorifiques (CCIT art. 64) ---
    'distinction_35_ans_mois' => 4.0,
    // Base de la gratification : salaire de base réel ou minimum de la catégorie (plus la prime d'ancienneté).
    'distinction_base' => 'salaire_reel',
    // Ancienneté retenue : ancienneté utile (périodes déduites) ou brute depuis l'embauche.
    'distinction_anciennete' => 'utile',
];
