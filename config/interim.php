<?php

/*
|--------------------------------------------------------------------------
| Paramètres de la gestion des intérims
|--------------------------------------------------------------------------
|
| Points non tranchés par le Code du travail (loi n°2021-012) ni par la CCIT
| 2026 : ils sont paramétrables ici. Aucune de ces valeurs ne doit être
| codée en dur dans le code métier.
|
*/

return [

    // RG-INT-03 — Durée maximale d'un intérim interne sans motif éligible.
    'duree_max_interim_mois' => (int) env('INTERIM_DUREE_MAX_MOIS', 2),

    // Mode de calcul des durées : « date_a_date » (calendaire, du 19/09 au 19/11).
    'mode_calcul_duree' => 'date_a_date',

    // RG-INT-10 — Proratisation de l'indemnité sur un mois incomplet :
    // « jours_reels_du_mois » ou « base_30 ».
    'prorata_indemnite' => 'jours_reels_du_mois',

    // RG-INT-03 — Les intérims successifs sur un même poste et un même titulaire
    // se cumulent pour le calcul du plafond.
    'cumul_interims_meme_poste' => true,

    // RG-INT-05 — Plafond de prolongation selon le motif.
    // Une durée en mois, ou « fin_conge » / « date_consolidation » lorsque le
    // plafond est une date saisie (fin du congé, consolidation ou guérison).
    'delais_legaux_par_motif' => [
        'maladie' => 6,               // Code art. 65 c ; CCIT art. 14
        'detention_preventive' => 6,  // Code art. 65 h
        'conges' => 'fin_conge',
        'at_mp' => 'date_consolidation',
    ],

    // RG-INT-09 — Un intérim « à régulariser » bloque la validation de la période de paie.
    'bloquer_cloture_paie_si_a_regulariser' => true,

    // RG-INT-06 — Barème des salaires minimums (Annexe II CCIT 2026).
    // Le barème est stocké dans la classification existante : un référentiel
    // dont le niveau_source vaut « ccit », versionné par debut_effet / fin_effet.
    // Les conventions sectorielles sont des référentiels de niveau
    // « convention_sectorielle » : elles priment sur la CCIT si elles sont plus favorables.
    'bareme' => [
        'niveau_source_ccit' => 'ccit',
        'niveau_source_convention_sectorielle' => 'convention_sectorielle',
        'echelon_reference' => 'A',
    ],

    // RG-INT-06 — Rubrique de paie de l'indemnité d'intérim.
    'rubrique_indemnite' => 'IND-INTERIM',

    // Tâches planifiées : alerte avant l'échéance des deux mois (J-15).
    'alerte_jours_avant_fin' => 15,

    // RG-REM-06 — Seuil d'alerte d'absence maladie du titulaire (Code art. 65 c).
    'alerte_maladie_titulaire_mois' => 6,

    // Travail intérimaire — décret n°2022-027/PR (Code art. 63) non intégré :
    // points d'extension pour la durée maximale des missions et les renouvellements.
    'missions' => [
        'duree_max_mois' => null,
        'renouvellements_max' => null,
    ],
];
