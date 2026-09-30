import type { Page } from '@playwright/test';
import { test, expect, unique } from './support/fixtures';
import { attendreModale, attendreToast, erreurDuChamp } from './support/ui';

/*
| I. Santé & sécurité au travail — CDC §4 7.1 (visites médicales), 7.2 (accidents
| et incidents), §6 « Confidentialité santé » : suivi administratif nominatif pour
| les RH, reporting agrégé sans nom ni avis pour la Direction et l'Auditeur.
*/

const SALARIE = 'GNASSINGBE Komlan (MAT-2026-0005)';

/** Date au format ISO (champ) et français (tableaux), décalée d'un nombre de jours aléatoire mais stable. */
function dateUnique(): { iso: string; fr: string } {
  const d = new Date();
  d.setDate(d.getDate() + 60 + Math.floor(Math.random() * 600));
  const iso = d.toISOString().slice(0, 10);
  const [a, m, j] = iso.split('-');
  return { iso, fr: `${j}/${m}/${a}` };
}

async function programmerVisite(page: Page, dateIso: string, typeIndex = 1): Promise<void> {
  await page.goto('/sst/visites/creer');
  await page.getByRole('combobox', { name: 'Salarié', exact: true }).selectOption({ label: SALARIE });
  await page.getByRole('combobox', { name: 'Type de visite' }).selectOption({ index: typeIndex });
  await page.getByLabel('Date prévue').fill(dateIso);
  await page.getByRole('button', { name: 'Programmer' }).click();
}

test.describe('Visites médicales (Responsable RH)', () => {
  test.use({ profil: 'rh' });

  test('I1 — programme puis renseigne une visite médicale', async ({ page }) => {
    const date = dateUnique();
    await programmerVisite(page, date.iso);
    await attendreToast(page, /Visite/);

    await expect(page).toHaveURL(/\/sst\/visites\/\d+$/);
    await page.goto('/sst/visites?q=GNASSINGBE');
    const ligne = page.getByRole('row').filter({ hasText: 'GNASSINGBE' }).filter({ hasText: date.fr });
    await expect(ligne).toBeVisible();

    await ligne.getByRole('button', { name: /^Actions sur la visite/ }).click();
    await ligne.getByRole('button', { name: 'Renseigner' }).click();
    const modale = await attendreModale(page, 'Renseigner la visite');
    await modale.getByRole('combobox', { name: 'Aptitude' }).selectOption({ index: 1 });
    await modale.getByLabel('Référence de l\'avis médical').fill(unique('AVIS'));
    await modale.getByRole('button', { name: 'Enregistrer' }).click();

    await attendreToast(page, /Visite|renseign/i);
    await expect(page.getByRole('row').filter({ hasText: 'GNASSINGBE' }).filter({ hasText: date.fr }))
      .toContainText(/Réalisée/);
  });

  test('I2 — refuse deux visites du même type le même jour pour le même salarié', async ({ page }) => {
    const date = dateUnique();
    await programmerVisite(page, date.iso, 2);
    await attendreToast(page, /Visite/);
    // Laisser la redirection différée aboutir avant de rouvrir le formulaire
    await expect(page).toHaveURL(/\/sst\/visites\/\d+$/);

    await programmerVisite(page, date.iso, 2);
    await expect(erreurDuChamp(page, 'Date prévue'))
      .toHaveText('Une visite de ce type est déjà programmée à cette date pour ce salarié.');
  });
});

test.describe('Accidents et incidents (Responsable RH)', () => {
  test.use({ profil: 'rh' });

  test('I3 — déclare un événement puis le clôture avec une synthèse obligatoire', async ({ page }) => {
    const intitule = unique('Chute E2E');
    await page.goto('/sst/evenements/creer');
    await page.getByRole('combobox', { name: 'Type d\'événement' }).selectOption({ index: 1 });
    await page.getByRole('textbox', { name: 'Intitulé', exact: true }).fill(intitule);
    await page.getByRole('textbox', { name: 'Localisation', exact: true }).fill('Atelier E2E');
    await page.getByRole('textbox', { name: 'Description détaillée' }).fill('Glissade sur un sol mouillé non signalé.');
    // Soumission AJAX : on attend la réponse du serveur avant la redirection différée (600 ms)
    const [reponse] = await Promise.all([
      page.waitForResponse((r) => r.request().method() === 'POST' && r.url().endsWith('/sst/evenements')),
      page.getByRole('button', { name: 'Déclarer' }).click(),
    ]);
    expect(reponse.status()).toBe(200);

    await expect(page).toHaveURL(/\/sst\/evenements\/\d+$/);
    await expect(page).toHaveTitle(new RegExp(intitule));

    await page.getByRole('button', { name: 'Clôturer' }).click();
    const modale = await attendreModale(page, 'Clôturer l\'événement');
    await modale.getByLabel('Synthèse de clôture').fill('Court');
    await modale.getByRole('button', { name: 'Clôturer' }).click();
    await expect(erreurDuChamp(modale, 'Synthèse de clôture'))
      .toHaveText('La synthèse doit contenir au moins 10 caractères.');

    await modale.getByLabel('Synthèse de clôture').fill('Signalisation installée et procédure de nettoyage revue.');
    await modale.getByRole('button', { name: 'Clôturer' }).click();
    await attendreToast(page, /clôtur/i);
    await expect(page.locator('.badge', { hasText: /Clôturé/ }).first()).toBeVisible();
  });
});

test.describe('Confidentialité santé (Direction générale)', () => {
  test.use({ profil: 'direction' });

  test('I4 — le reporting agrégé n\'expose aucun nom ni avis médical', async ({ page }) => {
    await page.goto('/sst/reporting');
    await expect(page.getByRole('heading', { level: 1 })).toBeVisible();

    const contenu = page.getByRole('main');
    for (const nom of ['DOSSOU', 'AMEGAN', 'KOFFI', 'SEDZRO', 'GNASSINGBE']) {
      await expect(contenu).not.toContainText(nom);
    }
    await expect(contenu).not.toContainText(/AVIS-\d/);
  });

  test('I5 — les registres nominatifs restent refusés en accès direct', async ({ page }) => {
    for (const url of ['/sst/visites', '/sst/evenements', '/sst/risques']) {
      expect((await page.goto(url))?.status(), url).toBe(403);
    }
  });
});
