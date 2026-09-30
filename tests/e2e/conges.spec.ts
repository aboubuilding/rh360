import type { Browser, Page } from '@playwright/test';
import { test, expect } from './support/fixtures';
import { sessionDe, type Profil } from './support/comptes';
import { BASE_URL } from './support/env';
import { attendreModale, attendreToast, erreurDuChamp, repondreConfirmation } from './support/ui';

/*
| F. Congés & absences — CDC §4 4.1 (demandes et autorisations), 4.2 (maternité),
| 4.4 (soldes). Saisie par le Responsable RH, autorisation par le DRH ;
| réservation puis consommation des jours ; reprise à partir de la date prévue.
*/

const SALARIE = 'KOFFI Yao (MAT-2026-0003)';

function dans(jours: number): string {
  const d = new Date();
  d.setDate(d.getDate() + jours);
  return d.toISOString().slice(0, 10);
}

async function pageDe(browser: Browser, profil: Profil): Promise<Page> {
  const contexte = await browser.newContext({ baseURL: BASE_URL, storageState: sessionDe(profil) });
  return contexte.newPage();
}

function statut(page: Page) {
  return page.locator('.card', { hasText: 'Statut' }).first().locator('.badge');
}

/** Saisit une demande de congé annuel (Responsable RH) et renvoie l'URL de sa fiche. */
async function saisirDemande(page: Page, debut: string, reprise: string): Promise<string> {
  await page.goto('/conges/demandes/creer');
  await page.getByRole('combobox', { name: 'Salarié', exact: true }).selectOption({ label: SALARIE });
  await page.getByRole('combobox', { name: 'Type de congé' }).selectOption({ label: 'Congé annuel payé' });
  await page.getByLabel('Date de début').fill(debut);
  await page.getByLabel('Date de reprise').fill(reprise);
  await page.getByRole('button', { name: 'Créer la demande' }).click();
  await expect(page).toHaveURL(/\/conges\/(demandes\/)?\d+$/);
  return page.url();
}

async function soumettre(page: Page): Promise<void> {
  await page.getByRole('button', { name: 'Soumettre' }).click();
  await repondreConfirmation(page, 'Oui, soumettre');
  await expect(statut(page)).toHaveText('Soumise');
}

test.describe('Circuit d\'une demande de congé', () => {
  test.describe.configure({ mode: 'serial' });
  let url = '';

  test('F1 — le Responsable RH saisit une demande, dont la durée est calculée', async ({ browser }) => {
    const rh = await pageDe(browser, 'rh');
    url = await saisirDemande(rh, dans(0), dans(3));

    await expect(statut(rh)).toHaveText('Brouillon');
    await expect(rh.locator('.card', { hasText: 'Durée' }).first()).toContainText('3');
    await rh.context().close();
  });

  test('F2 — le Responsable RH soumet la demande mais ne peut pas l\'autoriser', async ({ browser }) => {
    const rh = await pageDe(browser, 'rh');
    await rh.goto(url);
    await soumettre(rh);
    await expect(rh.getByRole('button', { name: 'Autoriser' })).toHaveCount(0);
    await rh.context().close();
  });

  test('F3 — le DRH ne peut pas refuser sans motif valable', async ({ browser }) => {
    const drh = await pageDe(browser, 'drh');
    await drh.goto(url);
    await drh.getByRole('button', { name: 'Refuser' }).click();
    const modale = await attendreModale(drh, 'Refuser la demande');
    await modale.getByLabel('Motif de refus').fill('non');
    await modale.getByRole('button', { name: 'Refuser' }).click();

    await expect(erreurDuChamp(modale, 'Motif de refus')).toBeVisible();
    await drh.reload();
    await expect(statut(drh)).toHaveText('Soumise');
    await drh.context().close();
  });

  test('F4 — le DRH autorise, programme puis constate le départ', async ({ browser }) => {
    const drh = await pageDe(browser, 'drh');
    await drh.goto(url);

    await drh.getByRole('button', { name: 'Autoriser' }).click();
    const autoriser = await attendreModale(drh, 'Autoriser la demande');
    await autoriser.getByLabel('Référence de l\'acte (optionnel)').fill('ACTE-E2E-001');
    await autoriser.getByRole('button', { name: 'Autoriser' }).click();
    await attendreToast(drh, 'Demande autorisée.');
    await expect(statut(drh)).toHaveText('Autorisée');

    await drh.getByRole('button', { name: 'Programmer' }).click();
    const programmer = await attendreModale(drh, 'Programmer la demande');
    await programmer.getByRole('button', { name: 'Programmer' }).click();
    await expect(statut(drh)).toHaveText('Programmée');

    // Départ aujourd'hui : le congé peut démarrer
    await drh.getByRole('button', { name: 'Démarrer' }).click();
    await repondreConfirmation(drh, 'Oui, démarrer');
    await expect(statut(drh)).toHaveText('En cours');
    await drh.context().close();
  });

  test('F5 — la reprise ne peut pas être confirmée avant la date prévue', async ({ browser }) => {
    const drh = await pageDe(browser, 'drh');
    await drh.goto(url);

    // Reprise prévue dans 3 jours : le bouton n'est pas proposé
    await expect(statut(drh)).toHaveText('En cours');
    await expect(drh.getByRole('button', { name: 'Confirmer la reprise' })).toHaveCount(0);
    await drh.context().close();
  });
});

test.describe('Règles de saisie et d\'autorisation', () => {
  test('F6 — refuse une date de reprise antérieure au départ', async ({ browser }) => {
    const rh = await pageDe(browser, 'rh');
    await rh.goto('/conges/demandes/creer');
    await rh.getByRole('combobox', { name: 'Salarié', exact: true }).selectOption({ label: SALARIE });
    await rh.getByRole('combobox', { name: 'Type de congé' }).selectOption({ label: 'Congé annuel payé' });
    await rh.getByLabel('Date de début').fill(dans(60));
    await rh.getByLabel('Date de reprise').fill(dans(55));
    await rh.getByRole('button', { name: 'Créer la demande' }).click();

    await expect(erreurDuChamp(rh, 'Date de reprise'))
      .toHaveText('La date de reprise doit être postérieure à la date de début.');
    await expect(rh).toHaveURL(/\/conges\/demandes\/creer$/);
    await rh.context().close();
  });

  test('F7 — refuse d\'autoriser une demande qui dépasse le solde disponible', async ({ browser }) => {
    const rh = await pageDe(browser, 'rh');
    const url = await saisirDemande(rh, dans(30), dans(400));
    await soumettre(rh);
    await rh.context().close();

    const drh = await pageDe(browser, 'drh');
    await drh.goto(url);
    await drh.getByRole('button', { name: 'Autoriser' }).click();
    const modale = await attendreModale(drh, 'Autoriser la demande');
    await modale.getByRole('button', { name: 'Autoriser' }).click();

    await attendreToast(drh, /solde|insuffisant/i, 'erreur');
    await drh.reload();
    await expect(statut(drh)).toHaveText('Soumise');
    await drh.context().close();
  });
});

test.describe('Registre maternité (données de santé)', () => {
  test('F8 — accessible au Responsable RH, refusé à la Direction', async ({ browser }) => {
    const rh = await pageDe(browser, 'rh');
    expect((await rh.goto('/conges/maternite'))?.status()).toBe(200);
    await rh.context().close();

    const direction = await pageDe(browser, 'direction');
    expect((await direction.goto('/conges/maternite'))?.status()).toBe(403);
    await direction.context().close();
  });
});
