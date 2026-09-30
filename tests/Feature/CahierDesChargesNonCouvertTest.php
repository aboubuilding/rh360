<?php

/*
| Fonctionnalités du cahier des charges v1.1 sans implémentation dans le code
| (aucune route, action ou service correspondant). Chaque todo référence la
| section du CDC ; le remplacer par un vrai test lors de l'implémentation.
*/

describe('Menu 2 — Dossiers RH', function () {
    it('exporte la liste filtrée des salariés en Excel et l\'imprime')->todo('CDC §4 2.1');
    it('affiche la liste « À traiter » et le reporting du personnel sur une période')->todo('CDC §4 2.6');
    it('exporte la liste des documents salariés')->todo('CDC §4 2.5');
});

describe('Menu 2 — Contrats et périodes d\'essai', function () {
    it('reprend automatiquement poste et classification à la date d\'effet')->todo('CDC §4 2.3');
    it('bloque un renouvellement d\'essai au-delà du plafond sauf dérogation documentée')->todo('CDC §4 2.4');
});

describe('Menu 3 — Carrière', function () {
    it('génère l\'acte de carrière en PDF ou Word')->todo('CDC §4 3.2');
    it('exporte la liste des avancements et le reporting de carrière en Excel')->todo('CDC §4 3.3 / 3.5');
});

describe('Menu 4 à 7 — exports', function () {
    it('exporte les demandes de congé en Excel / PDF')->todo('CDC §4 4.1');
    it('exporte les soldes et le référentiel des types de congés')->todo('CDC §4 4.4 / 4.5');
    it('exporte le journal de paie')->todo('CDC §4 5.1 — vérifier la route paie.periodes.journal');
    it('exporte les indicateurs SST (accidents, risques, EPI, habilitations) en CSV')->todo('CDC §4 7.2 à 7.5');
});

describe('Menu 8 — Administration', function () {
    it('configure l\'entreprise et le premier compte au premier lancement')->todo('CDC §4 8.1 — écran d\'installation');
    it('permet à chaque utilisateur de modifier son profil et son mot de passe')->todo('CDC §4 8.4');
    it('importe l\'organisation et la grille salariale depuis un modèle Excel')->todo('CDC §4 8.2 / 8.3');
});

describe('Règles transversales', function () {
    it('refuse une modification si la fiche a changé depuis son ouverture (verrou optimiste)')
        ->todo('CDC §6 — colonnes « revision » présentes mais non contrôlées à la mise à jour');
    it('ignore un double envoi du même formulaire de création (clé de soumission)')
        ->todo('CDC §6 — cle_soumission générée côté serveur, jamais reçue du formulaire');
    it('produit les exports Excel avec onglets Critères, Synthèse, Registre')->todo('CDC §6 — Exports et impressions');
});
