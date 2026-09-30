import type { Browser, Page } from '@playwright/test';
import { test, expect, unique } from './support/fixtures';
import { sessionDe, type Profil } from './support/comptes';
import { BASE_URL } from './support/env';
import { attendreModale, attendreToast, erreurDuChamp, repondreConfirmation } from './support/ui';

/*
| D. Contrats & avenants — CDC §4 2.3, §6 (circuit brouillon → soumis → validé → signé,
| motif obligatoire, contrat signé non modifiable, évolution par avenant).
| Le circuit est joué par les vrais profils : préparation et signature par le
| Responsable RH, validation par le DRH.
*/

/** Page connectée sous un profil donné, dans un contexte séparé (plusieurs acteurs dans un même test). */
async function pageDe(browser: Browser, profil: Profil): Promise<Page> {
  const contexte = await browser.newContext({ baseURL: BASE_URL, storageState: sessionDe(profil) });
  return contexte.newPage();
}

/** Crée un contrat en brouillon (Responsable RH) et renvoie l'URL de sa fiche. */
async function creerContrat(page: Page, reference: string): Promise<string> {
  await page.goto('/contrats/creer');
  await page.getByRole('combobox', { name: 'Salarié', exact: true }).selectOption({ index: 1 });
  await page.getByLabel('Référence du contrat').fill(reference);
  await page.getByLabel('Type de contrat').selectOption({ index: 1 });
  await page.getByRole('combobox', { name: 'Poste', exact: true }).selectOption({ index: 1 });
  await page.getByLabel('Position de classification').selectOption({ index: 1 });
  await page.getByRole('button', { name: 'Créer le contrat' }).click();

  await expect(page).toHaveURL(/\/contrats\/\d+$/);
  return page.url();
}

function statut(page: Page) {
  return page.locator('.card', { hasText: 'Statut' }).first().locator('.badge');
}

test.describe('Circuit complet d\'un contrat', () => {
  test.describe.configure({ mode: 'serial' });

  const reference = unique('CTR-E2E');
  let urlContrat = '';

  test('D1 — le Responsable RH crée un contrat en brouillon', async ({ browser }) => {
    const rh = await pageDe(browser, 'rh');
    urlContrat = await creerContrat(rh, reference);

    await expect(rh.getByRole('heading', { level: 1 })).toContainText(reference);
    await expect(statut(rh)).toHaveText('Brouillon');
    await rh.context().close();
  });

  test('D2 — le Responsable RH soumet le contrat mais ne peut pas le valider', async ({ browser }) => {
    const rh = await pageDe(browser, 'rh');
    await rh.goto(urlContrat);
    await rh.getByRole('button', { name: 'Soumettre' }).click();
    await repondreConfirmation(rh, 'Oui, soumettre');

    await attendreToast(rh, 'Contrat soumis.');
    await expect(statut(rh)).toHaveText('Soumis');
    await expect(rh.getByRole('button', { name: 'Valider' })).toHaveCount(0);
    await rh.context().close();
  });

  test('D3 — le DRH ne peut pas retourner le contrat au brouillon sans motif valable', async ({ browser }) => {
    const drh = await pageDe(browser, 'drh');
    await drh.goto(urlContrat);
    await drh.getByRole('button', { name: 'Retourner' }).click();

    const modale = await attendreModale(drh, 'Retourner au brouillon');
    await modale.getByLabel('Motif').fill('abc');
    await modale.getByRole('button', { name: 'Retourner au brouillon' }).click();

    await expect(erreurDuChamp(modale, 'Motif')).toBeVisible();
    await drh.reload();
    await expect(statut(drh)).toHaveText('Soumis');
    await drh.context().close();
  });

  test('D4 — le DRH valide le contrat', async ({ browser }) => {
    const drh = await pageDe(browser, 'drh');
    await drh.goto(urlContrat);
    await drh.getByRole('button', { name: 'Valider' }).click();

    const modale = await attendreModale(drh, 'Valider le contrat');
    await modale.getByRole('button', { name: 'Valider' }).click();

    await attendreToast(drh, 'Contrat validé.');
    await expect(statut(drh)).toHaveText('Validé');
    await drh.context().close();
  });

  test('D5 — le Responsable RH référence la signature', async ({ browser }) => {
    const rh = await pageDe(browser, 'rh');
    await rh.goto(urlContrat);
    await rh.getByRole('button', { name: 'Référencer la signature' }).click();

    const modale = await attendreModale(rh, 'Référencer la signature');
    await modale.getByLabel('Référence du document signé').fill(`${reference}-SIG`);
    await modale.getByRole('button', { name: 'Référencer la signature' }).click();

    await attendreToast(rh, 'Signature référencée.');
    await expect(statut(rh)).toHaveText('Signé / référencé');
    await rh.context().close();
  });

  test('D6 — un contrat signé n\'est plus modifiable et évolue par avenant', async ({ browser }) => {
    const rh = await pageDe(browser, 'rh');
    await rh.goto(urlContrat);

    await expect(rh.getByRole('link', { name: 'Modifier' })).toHaveCount(0);
    expect((await rh.goto(`${urlContrat}/modifier`))?.status()).toBe(403);

    await rh.goto(urlContrat);
    await rh.getByRole('link', { name: 'Créer un avenant' }).click();
    // Valeurs reprises du contrat d'origine (§2.3)
    await expect(rh.getByRole('combobox', { name: 'Type de contrat' })).not.toHaveValue('');
    await expect(rh.getByRole('combobox', { name: 'Poste', exact: true })).not.toHaveValue('');
    await expect(rh.getByRole('combobox', { name: 'Position de classification' })).not.toHaveValue('');
    const referenceAvenant = `${reference}-AV1`;
    await rh.getByLabel('Référence du contrat').fill(referenceAvenant);
    await rh.getByRole('button', { name: 'Créer l\'avenant' }).click();

    await expect(rh).toHaveURL(/\/contrats\/\d+$/);
    await expect(rh.getByRole('heading', { level: 1 })).toContainText(`Avenant ${referenceAvenant}`);
    // Lien vers le contrat d'origine (encadré et fil d'Ariane)
    await expect(rh.getByRole('main').getByRole('link', { name: reference, exact: true }).first()).toBeVisible();
    await rh.context().close();
  });

  test('D7 — l\'Auditeur consulte le contrat signé sans aucune action', async ({ browser }) => {
    const auditeur = await pageDe(browser, 'auditeur');
    await auditeur.goto(urlContrat);

    await expect(statut(auditeur)).toHaveText('Signé / référencé');
    for (const action of ['Soumettre', 'Valider', 'Retourner', 'Annuler', 'Référencer la signature']) {
      await expect(auditeur.getByRole('button', { name: action, exact: true })).toHaveCount(0);
    }
    await expect(auditeur.getByRole('link', { name: 'Créer un avenant' })).toHaveCount(0);
    await auditeur.context().close();
  });
});

test.describe('Validations du formulaire', () => {
  test.use({ profil: 'rh' });

  test('D8 — refuse une référence déjà utilisée dans l\'entreprise', async ({ page }) => {
    await page.goto('/contrats/creer');
    await page.getByRole('combobox', { name: 'Salarié', exact: true }).selectOption({ index: 1 });
    await page.getByLabel('Référence du contrat').fill('CTR-2026-0001');
    await page.getByLabel('Type de contrat').selectOption({ index: 1 });
    await page.getByRole('combobox', { name: 'Poste', exact: true }).selectOption({ index: 1 });
    await page.getByLabel('Position de classification').selectOption({ index: 1 });
    await page.getByRole('button', { name: 'Créer le contrat' }).click();

    await expect(erreurDuChamp(page, 'Référence du contrat'))
      .toHaveText('Cette référence de contrat est déjà utilisée dans l\'entreprise.');
    await expect(page).toHaveURL(/\/contrats\/creer$/);
  });

  test('D9 — refuse une date de fin antérieure au début', async ({ page }) => {
    await page.goto('/contrats/creer');
    await page.getByRole('combobox', { name: 'Salarié', exact: true }).selectOption({ index: 1 });
    await page.getByLabel('Référence du contrat').fill(unique('CTR-DATES'));
    await page.getByLabel('Type de contrat').selectOption({ index: 1 });
    await page.getByLabel('Date de début').fill('2026-06-01');
    await page.getByLabel('Date de fin (si CDD)').fill('2026-05-01');
    await page.getByRole('combobox', { name: 'Poste', exact: true }).selectOption({ index: 1 });
    await page.getByLabel('Position de classification').selectOption({ index: 1 });
    await page.getByRole('button', { name: 'Créer le contrat' }).click();

    await expect(erreurDuChamp(page, 'Date de fin (si CDD)'))
      .toHaveText('La date de fin doit être postérieure à la date de début.');
  });
});

test.describe('Annulation', () => {
  test('D10 — le DRH annule un contrat soumis avec un motif', async ({ browser }) => {
    const rh = await pageDe(browser, 'rh');
    const url = await creerContrat(rh, unique('CTR-ANNUL'));
    await rh.getByRole('button', { name: 'Soumettre' }).click();
    await repondreConfirmation(rh, 'Oui, soumettre');
    await attendreToast(rh, 'Contrat soumis.');
    await rh.context().close();

    const drh = await pageDe(browser, 'drh');
    await drh.goto(url);
    await drh.getByRole('button', { name: 'Annuler', exact: true }).click();
    const modale = await attendreModale(drh, 'Annuler le contrat');
    await modale.getByLabel('Motif d\'annulation').fill('Erreur de saisie sur le poste');
    await modale.getByRole('button', { name: 'Confirmer l\'annulation' }).click();

    await attendreToast(drh, 'Contrat annulé.');
    await expect(statut(drh)).toHaveText('Annulé');
    await expect(drh.getByRole('button', { name: 'Annuler', exact: true })).toHaveCount(0);
    await drh.context().close();
  });
});
