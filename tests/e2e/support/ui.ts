import { expect, type Locator, type Page, type Response } from '@playwright/test';

/**
 * Helpers pour les spécificités de l'interface Blade d'EXPERT RH 360 :
 * toasts (#toast-container, rôles status / alert), confirmations SweetAlert2,
 * modales Bootstrap, erreurs de validation Laravel affichées sous les champs,
 * et requêtes AJAX (jQuery) dont on attend la réponse.
 */

/** Attend un toast de succès (role=status) ou d'erreur (role=alert) contenant le texte. */
export async function attendreToast(page: Page, texte: string | RegExp, type: 'succes' | 'erreur' = 'succes'): Promise<void> {
  const conteneur = page.locator('#toast-container');
  await expect(conteneur.getByRole(type === 'erreur' ? 'alert' : 'status').filter({ hasText: texte }).first())
    .toBeVisible();
}

/** Confirme (ou annule) la boîte SweetAlert2 ouverte. */
export async function repondreConfirmation(page: Page, bouton: string | RegExp): Promise<void> {
  const boite = page.locator('.swal2-popup');
  await expect(boite).toBeVisible();
  await boite.getByRole('button', { name: bouton }).click();
}

/** Modale Bootstrap actuellement ouverte. */
export function modaleOuverte(page: Page): Locator {
  return page.locator('.modal.show');
}

/** Attend l'ouverture d'une modale dont le titre contient le texte, et la renvoie. */
export async function attendreModale(page: Page, titre: string | RegExp): Promise<Locator> {
  const modale = page.locator('.modal.show').filter({ has: page.locator('.modal-title', { hasText: titre }) });
  await expect(modale).toBeVisible();
  return modale;
}

/** Message d'erreur de validation affiché sous un champ donné (classe Bootstrap invalid-feedback). */
export function erreurSous(champ: Locator): Locator {
  return champ.locator('xpath=following-sibling::div[contains(@class,"invalid-feedback")][1]');
}

/** Message d'erreur de validation affiché sous le champ portant ce libellé. */
export function erreurDuChamp(
  conteneur: Page | Locator,
  libelle: string | RegExp,
  options: { exact?: boolean } = {},
): Locator {
  return erreurSous(conteneur.getByLabel(libelle, options));
}

/** Clique puis attend la réponse de la requête (AJAX ou formulaire) correspondant à l'URL. */
export async function cliquerEtAttendre(
  page: Page,
  cible: Locator,
  url: string | RegExp,
  methode = 'POST',
): Promise<Response> {
  const [reponse] = await Promise.all([
    page.waitForResponse((r) => r.request().method() === methode
      && (typeof url === 'string' ? r.url().includes(url) : url.test(r.url()))),
    cible.click(),
  ]);
  return reponse;
}

/** Ouvre le menu d'actions « ⋮ » d'une ligne de tableau contenant le texte. */
export async function ouvrirActionsDeLaLigne(page: Page, texteLigne: string | RegExp): Promise<Locator> {
  const ligne = page.getByRole('row').filter({ hasText: texteLigne }).first();
  await ligne.locator('[data-bs-toggle="dropdown"]').click();
  return ligne.locator('.dropdown-menu.show');
}

/** Vérifie qu'une URL renvoie le code HTTP attendu (accès direct, contrôle de permission). */
export async function statutHttp(page: Page, url: string): Promise<number> {
  const reponse = await page.goto(url);
  return reponse?.status() ?? 0;
}
