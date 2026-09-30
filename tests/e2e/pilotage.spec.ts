import { test, expect } from './support/fixtures';
import type { Profil } from './support/comptes';

/*
| B. Tableau de bord, recherche et notifications — CDC §4 1.1 et 1.2.
| Contenu filtré selon les permissions ; chaque indicateur ouvre la liste
| correspondante ; recherche d'un salarié par nom ou matricule (2 caractères min.).
*/

const WIDGETS = ['Dossiers RH', 'Contrats', 'Carrière & Mobilité', 'Congés & Absences', 'Documents', 'Santé & Sécurité', 'Paie'];

test.describe('Tableau de bord', () => {
  test('B1 — le Super administrateur voit tous les indicateurs', async ({ page }) => {
    await page.goto('/tableau-de-bord');
    for (const titre of WIDGETS) {
      await expect(page.getByRole('region', { name: titre })).toBeVisible();
    }
  });

  test.describe('Auditeur', () => {
    test.use({ profil: 'auditeur' });

    test('B2 — ne voit que les indicateurs de son périmètre, sans données nominatives', async ({ page }) => {
      await page.goto('/tableau-de-bord');
      for (const titre of ['Contrats', 'Paie', 'Santé & Sécurité']) {
        await expect(page.getByRole('region', { name: titre })).toBeVisible();
      }
      for (const titre of ['Dossiers RH', 'Carrière & Mobilité', 'Congés & Absences', 'Documents']) {
        await expect(page.getByRole('region', { name: titre })).toHaveCount(0);
      }
    });
  });

  for (const profil of ['rh', 'direction', 'auditeur', 'manager'] as Profil[]) {
    test.describe(`Profil ${profil}`, () => {
      test.use({ profil });

      test(`B3 — chaque indicateur du tableau de bord (${profil}) ouvre une page accessible`, async ({ page }) => {
        await page.goto('/tableau-de-bord');
        const liens = await page.locator('main section[data-testid^="widget-"] a[href]')
          .evaluateAll((els) => els.map((e) => (e as HTMLAnchorElement).getAttribute('href') ?? ''));

        const erreurs: string[] = [];
        for (const href of [...new Set(liens)]) {
          const statut = (await page.goto(href))?.status() ?? 0;
          if (statut >= 400) erreurs.push(`${statut} ${href}`);
        }
        expect(erreurs, 'indicateurs menant à une erreur').toEqual([]);
      });
    });
  }

  test.describe('Responsable RH', () => {
    test.use({ profil: 'rh' });

    test('B4 — l\'indicateur « Dossiers incomplets » ouvre la liste filtrée', async ({ page }) => {
      await page.goto('/tableau-de-bord');
      await page.getByRole('region', { name: 'Dossiers RH' }).getByRole('link', { name: /Dossiers incomplets/ }).click();

      await expect(page).toHaveURL(/\/salaries\?.*completude=incomplets/);
      await expect(page.getByRole('combobox', { name: 'Complétude du dossier' })).toHaveValue('incomplets');
    });
  });
});

test.describe('Recherche rapide (Ctrl+K)', () => {
  test.use({ profil: 'rh' });

  test('B5 — trouve un salarié par son nom et ouvre sa fiche', async ({ page }) => {
    await page.goto('/tableau-de-bord');
    await page.keyboard.press('Control+k');

    const recherche = page.getByRole('dialog', { name: 'Recherche' });
    await expect(recherche).toBeVisible();
    await recherche.getByRole('searchbox').fill('DOSS');

    const resultat = recherche.getByRole('link', { name: /DOSSOU Kofi/ });
    await expect(resultat).toBeVisible();
    await expect(resultat).toContainText('MAT-2026-0001');
    await resultat.click();
    await expect(page).toHaveURL(/\/salaries\/\d+$/);
    await expect(page.getByRole('heading', { level: 1 })).toContainText('DOSSOU Kofi');
  });

  test('B6 — attend au moins 2 caractères avant de rechercher', async ({ page }) => {
    await page.goto('/tableau-de-bord');
    let appels = 0;
    page.on('request', (r) => { if (r.url().includes('/recherche/salaries')) appels++; });

    await page.getByRole('button', { name: /Rechercher un salarié/ }).click();
    const recherche = page.getByRole('dialog', { name: 'Recherche' });
    await recherche.getByRole('searchbox').fill('D');
    await page.waitForTimeout(600); // délai de frappe (300 ms) écoulé

    await expect(recherche).toContainText('Commencez à taper');
    expect(appels).toBe(0);
  });

  test('B7 — recherche aussi par matricule', async ({ page }) => {
    await page.goto('/tableau-de-bord');
    await page.keyboard.press('Control+k');
    await page.getByRole('dialog', { name: 'Recherche' }).getByRole('searchbox').fill('MAT-2026-0002');

    await expect(page.getByRole('dialog', { name: 'Recherche' }).getByRole('link', { name: /AMEGAN Ama/ })).toBeVisible();
  });
});

test.describe('Recherche non autorisée', () => {
  test.use({ profil: 'auditeur' });

  test('B8 — la recherche des salariés n\'est pas proposée à l\'Auditeur', async ({ page }) => {
    await page.goto('/tableau-de-bord');
    await expect(page.getByRole('button', { name: /Rechercher un salarié/ })).toHaveCount(0);
    expect((await page.request.get('/recherche/salaries?q=DOSSOU')).status()).toBe(403);
  });
});

test.describe('Notifications contractuelles', () => {
  test.use({ profil: 'drh' });

  test('B9 — la cloche mène à la boîte de réception des notifications', async ({ page }) => {
    await page.goto('/tableau-de-bord');
    await page.getByTestId('cloche-notifications').click();

    await expect(page).toHaveURL(/\/contrats\/notifications$/);
    await expect(page.getByRole('heading', { level: 1 })).toContainText(/Notifications/);
  });
});
