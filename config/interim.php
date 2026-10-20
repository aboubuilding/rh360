<?php

/*
|--------------------------------------------------------------------------
| Paramètres de la gestion des intérims — valeurs par défaut
|--------------------------------------------------------------------------
|
| Valeurs prévues par le Code du travail (loi n°2021-012) et la CCIT 2026,
| ou, pour les points que les textes ne tranchent pas, valeurs retenues par
| défaut. Chaque entreprise peut les modifier depuis l'écran « Paramètres des
| intérims » (table parametres_interim) ; le catalogue des paramètres, avec
| leur référence, est dans App\Domain\Interim\Services\CatalogueParametresInterim.
| Aucune de ces valeurs ne doit être codée en dur dans le code métier.
|
*/

return [

    // RG-INT-03 — Durée maximale d'un intérim interne sans motif éligible (CCIT art. 13).
    'duree_max_interim_mois' => 2,

    // Mode de calcul des durées : « date_a_date » (calendaire, du 19/09 au 19/11).
    'mode_calcul_duree' => 'date_a_date',

    // RG-INT-03 — Les intérims successifs sur un même poste et un même titulaire
    // se cumulent pour le calcul du plafond.
    'cumul_interims_meme_poste' => true,

    // RG-INT-05 — Délais légaux des motifs exprimés en mois.
    'delai_maladie_mois' => 6,                 // Code art. 65 c ; CCIT art. 14
    'delai_detention_preventive_mois' => 6,    // Code art. 65 h

    // RG-INT-05 — Point de départ des délais en mois : le délai légal est celui de la
    // suspension du contrat du titulaire, il court donc depuis le début de son absence.
    // « debut_absence_titulaire » ou « debut_interim ».
    'origine_delai_legal' => 'debut_absence_titulaire',

    // RG-INT-05 — Plafonds fixés par une date : fin du congé, consolidation ou guérison.
    'delais_par_date' => [
        'conges' => 'fin_conge',
        'at_mp' => 'date_consolidation',
    ],

    // RG-INT-10 — Proratisation de l'indemnité sur un mois incomplet :
    // « jours_reels_du_mois » ou « base_30 ».
    'prorata_indemnite' => 'jours_reels_du_mois',

    // RG-INT-09 — Un intérim « à régulariser » bloque la validation de la période de paie.
    'bloquer_cloture_paie_si_a_regulariser' => true,

    // RG-INT-06 — Barème des salaires minimums (Annexe II CCIT 2026), stocké dans la
    // classification. Sans référentiel choisi, on retient ceux dont le niveau_source
    // vaut « ccit » (et « convention_sectorielle » pour les conventions de branche).
    'referentiel_bareme_id' => null,
    'referentiels_conventions_ids' => [],
    'niveau_source_ccit' => 'ccit',
    'niveau_source_convention_sectorielle' => 'convention_sectorielle',
    'echelon_reference' => 'A',

    // RG-INT-06 — Rubrique de paie de l'indemnité d'intérim.
    'rubrique_indemnite' => 'IND-INTERIM',

    // Tâches planifiées : alerte avant l'échéance (J-15).
    'alerte_jours_avant_fin' => 15,

    // RG-REM-06 — Seuil d'alerte d'absence maladie du titulaire (Code art. 65 c).
    'alerte_maladie_titulaire_mois' => 6,

    // Travail intérimaire — décret n°2022-027/PR (Code art. 63) non intégré :
    // points d'extension pour la durée maximale des missions et les renouvellements.
    'missions_duree_max_mois' => null,
    'missions_renouvellements_max' => null,
];
