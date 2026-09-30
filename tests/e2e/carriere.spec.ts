import type { Browser, Page } from '@playwright/test';
import { test, expect } from './support/fixtures';
import { sessionDe, type Profil } from './support/comptes';
import { BASE_URL } from './support/env';
import { attendreModale, attendreToast, erreurDuChamp, repondreConfirmation } from './support/ui';

/*
| E. Carrière & mobilité — CDC §4 3.2 (actes), 3.3 (avancements), 3.4 (intérims),
| §6 (circuit proposition → contrôle RH → validation DRH → programmation → effet,
| application à la date d'effet).
*/

const SALARIE = 'SEDZRO Akouvi (MAT-2026-0004)';

async function pageDe(browser: Browser, profil: Profil): Promise<Page> {
  const contexte = await browser.newContext({ baseURL: BASE_URL, storageState: sessionDe(profil) });
  return contexte.newPage();
}

function statut(page: Page) {
  return page.locator('.card', { hasText: 'Statut' }).first().locator('.badge').first();
}

function dans(jours: number): string {
  const d = new Date();
  d.setDate(d.getDate() + jours);
  return d.toISOString().slice(0, 10);
}

/** Crée un acte de mutation vers un poste cible (Responsable RH), renvoie l'URL de sa fiche. */
async function creerMutation(page: Page, posteCible: string): Promise<string> {
  await page.goto('/carriere/mouvements/creer');
  await page.getByRole('combobox', { name: 'Salarié', exact: true }).selectOption({ label: SALARIE });
  await page.getByRole('combobox', { name: 'Type de mouvement' }).selectOption({ label: 'Mutation' });
  // Carte dont l'en-tête direct est « Situation cible » (le formulaire est lui-même une .card)
  const cible = page.locator('.card').filter({ has: page.locator('> .card-header', { hasText: 'Situation cible' }) });
  await cible.getByLabel('Poste').selectOption({ label: posteCible });
  await page.getByLabel('Motif', { exact: true }).fill('Mutation E2E');
  await page.getByRole('button', { name: 'Créer', exact: true }).click();

  await expect(page).toHaveURL(/\/carriere\/mouvements\/\d+$/);
  return page.url();
}

/** Fait parcourir à un acte tout le circuit jusqu'au statut « Validé ». */
async function jusquAValide(browser: Browser, url: string): Promise<void> {
  const rh = await pageDe(browser, 'rh');
  await rh.goto(url);
  await rh.getByRole('button', { name: 'Soumettre' }).click();
  await repondreConfirmation(rh, 'Oui, soumettre');
  await expect(statut(rh)).toHaveText('Proposé');

  await rh.getByRole('button', { name: 'Contrôler' }).click();
  const controle = await attendreModale(rh, 'Contrôle RH');
  await controle.getByRole('button', { name: 'Valider le contrôle' }).click();
  await attendreToast(rh, /contrôlé/i);
  await expect(statut(rh)).toHaveText('À vérifier');
  await rh.context().close();

  const drh = await pageDe(browser, 'drh');
  await drh.goto(url);
  await drh.getByRole('button', { name: 'Vérifier' }).click();
  const verification = await attendreModale(drh, 'Vérification DRH');
  await verification.getByRole('button', { name: 'Vérifier' }).click();
  await attendreToast(drh, 'Mouvement vérifié.');
  await expect(statut(drh)).toHaveText('Vérifié');

  await drh.getByRole('button', { name: 'Valider' }).click();
  const validation = await attendreModale(drh, 'Validation DRH');
  await validation.getByRole('button', { name: 'Valider' }).click();
  await attendreToast(drh, 'Mouvement validé.');
  await expect(statut(drh)).toHaveText('Validé');
  await drh.context().close();
}

test.describe('Circuit d\'un acte de carrière', () => {
  // Plusieurs acteurs et tout le circuit par test : délai triplé
  test.slow();

  test('E1 — le Responsable RH propose et contrôle, mais ne vérifie ni ne valide', async ({ browser }) => {
    const rh = await pageDe(browser, 'rh');
    const url = await creerMutation(rh, 'Secrétaire');
    await expect(statut(rh)).toHaveText('Brouillon');

    await rh.getByRole('button', { name: 'Soumettre' }).click();
    await repondreConfirmation(rh, 'Oui, soumettre');
    await expect(statut(rh)).toHaveText('Proposé');

    await rh.getByRole('button', { name: 'Contrôler' }).click();
    const modale = await attendreModale(rh, 'Contrôle RH');
    await modale.getByRole('button', { name: 'Valider le contrôle' }).click();
    await expect(statut(rh)).toHaveText('À vérifier');

    await expect(rh.getByRole('button', { name: 'Vérifier' })).toHaveCount(0);
    await expect(rh.getByRole('button', { name: 'Valider', exact: true })).toHaveCount(0);
    expect(url).toMatch(/\/carriere\/mouvements\/\d+$/);
    await rh.context().close();
  });

  test('E2 — le DRH ne peut pas rejeter sans motif valable', async ({ browser }) => {
    const rh = await pageDe(browser, 'rh');
    const url = await creerMutation(rh, 'Secrétaire');
    await rh.getByRole('button', { name: 'Soumettre' }).click();
    await repondreConfirmation(rh, 'Oui, soumettre');
    await expect(statut(rh)).toHaveText('Proposé');
    await rh.getByRole('button', { name: 'Contrôler' }).click();
    await (await attendreModale(rh, 'Contrôle RH')).getByRole('button', { name: 'Valider le contrôle' }).click();
    await expect(statut(rh)).toHaveText('À vérifier');
    await rh.context().close();

    const drh = await pageDe(browser, 'drh');
    await drh.goto(url);
    await drh.getByRole('button', { name: 'Rejeter' }).click();
    const modale = await attendreModale(drh, 'Rejeter le mouvement');
    await modale.getByLabel('Motif de rejet').fill('non');
    await modale.getByRole('button', { name: 'Rejeter' }).click();
    await expect(erreurDuChamp(modale, 'Motif de rejet')).toBeVisible();

    await modale.getByLabel('Motif de rejet').fill('Poste cible déjà pourvu');
    await modale.getByRole('button', { name: 'Rejeter' }).click();
    await attendreToast(drh, /rejeté/i);
    await expect(statut(drh)).toHaveText('Rejeté');
    await drh.context().close();
  });

  test('E3 — un acte validé à effet rétroactif est appliqué immédiatement', async ({ browser }) => {
    const rh = await pageDe(browser, 'rh');
    const url = await creerMutation(rh, 'Ingénieur');
    await rh.context().close();
    await jusquAValide(browser, url);

    const drh = await pageDe(browser, 'drh');
    await drh.goto(url);
    await drh.getByRole('button', { name: 'Programmer' }).click();
    const modale = await attendreModale(drh, 'Programmer le mouvement');
    await modale.getByLabel('Date d\'effet').fill(dans(-10));
    await modale.getByRole('button', { name: 'Programmer' }).click();

    await attendreToast(drh, /effet rétroactif appliqué/);
    await expect(statut(drh)).toHaveText('Effectif');

    // L'affectation du salarié a changé
    await drh.getByRole('main').getByRole('link', { name: 'SEDZRO Akouvi' }).first().click();
    await drh.getByRole('tab', { name: 'Affectations' }).click();
    const enCours = drh.locator('#tab-affectations tbody tr').filter({ hasText: 'Oui' });
    await expect(enCours).toHaveCount(1);
    await expect(enCours).toContainText('Ingénieur');
    await drh.context().close();
  });

  test('E4 — un acte à date d\'effet future est programmé sans être appliqué', async ({ browser }) => {
    const rh = await pageDe(browser, 'rh');
    const url = await creerMutation(rh, 'Comptable');
    await rh.context().close();
    await jusquAValide(browser, url);

    const drh = await pageDe(browser, 'drh');
    await drh.goto(url);
    await drh.getByRole('button', { name: 'Programmer' }).click();
    const modale = await attendreModale(drh, 'Programmer le mouvement');
    await modale.getByLabel('Date d\'effet').fill(dans(30));
    await modale.getByRole('button', { name: 'Programmer' }).click();

    await attendreToast(drh, 'Mouvement programmé.');
    await expect(statut(drh)).toHaveText('Programmé');
    await expect(drh.getByRole('button', { name: 'Appliquer maintenant' })).toHaveCount(0);
    await drh.context().close();
  });
});

test.describe('Avancements et intérims', () => {
  test.use({ profil: 'rh' });

  test('E5 — liste les avancements et prépare les propositions automatiques', async ({ page }) => {
    await page.goto('/carriere/avancements');
    await expect(page.getByRole('heading', { level: 1 })).toContainText(/Avancement/);

    await page.getByRole('button', { name: /Préparer/ }).click();
    await repondreConfirmation(page, 'Oui, préparer');
    await attendreToast(page, /proposition\(s\)/);
  });

  test('E6 — crée un intérim sans modifier le poste permanent', async ({ page }) => {
    await page.goto('/carriere/interims/creer');
    await page.getByRole('combobox', { name: 'Salarié', exact: true }).selectOption({ label: SALARIE });
    await page.getByRole('combobox', { name: 'Poste assuré', exact: true }).selectOption({ label: 'Directeur général' });
    await page.getByLabel('Motif de l\'intérim').fill('Intérim E2E');
    await page.getByRole('button', { name: 'Créer l\'intérim' }).click();

    await expect(page).toHaveURL(/\/carriere\/interims\/\d+$/);
    await expect(page.locator('body')).toContainText('Directeur général');
  });
});
