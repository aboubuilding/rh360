import type { Browser, Page } from '@playwright/test';
import { test, expect } from './support/fixtures';
import { sessionDe, type Profil } from './support/comptes';
import { BASE_URL } from './support/env';
import { attendreModale, attendreToast, repondreConfirmation } from './support/ui';

/*
| G. Paie — CDC §4 5.1 et §6 « Paie » : préparation par le Responsable RH,
| calcul et validation par le DRH ; une période validée est figée (ni saisie,
| ni recalcul) ; réouverture exceptionnelle réservée au Super administrateur.
| Données du seed : mois précédent « Validée (figée) », mois en cours « Ouverte ».
*/

async function pageDe(browser: Browser, profil: Profil): Promise<Page> {
  const contexte = await browser.newContext({ baseURL: BASE_URL, storageState: sessionDe(profil) });
  return contexte.newPage();
}

/** Ouvre, depuis la liste (cartes <article>), la première période ayant le statut indiqué. */
async function ouvrirPeriode(page: Page, statut: RegExp): Promise<void> {
  await page.goto('/paie/periodes');
  const carte = page.getByRole('article').filter({ has: page.locator('.badge', { hasText: statut }) }).first();
  await carte.getByRole('link', { name: 'Consulter' }).click();
  await expect(page).toHaveURL(/\/paie\/periodes\/\d+$/);
}

test.describe('Période validée (figée)', () => {
  test('G1 — n\'offre ni saisie ni recalcul, mais permet la réouverture au Super administrateur', async ({ page }) => {
    await ouvrirPeriode(page, /Validée/);

    await expect(page.getByRole('button', { name: 'Saisir un élément' })).toHaveCount(0);
    await expect(page.getByRole('button', { name: 'Calculer la paie' })).toHaveCount(0);
    await expect(page.getByRole('button', { name: 'Rouvrir' })).toBeVisible();
  });

  test('G2 — exige un motif d\'au moins 10 caractères pour rouvrir', async ({ page }) => {
    await ouvrirPeriode(page, /Validée/);
    await page.getByRole('button', { name: 'Rouvrir' }).click();

    const boite = page.locator('.swal2-popup');
    await boite.locator('.swal2-textarea').fill('Erreur');
    await boite.getByRole('button', { name: 'Oui, rouvrir' }).click();
    await expect(boite.locator('.swal2-validation-message')).toHaveText('Le motif doit contenir au moins 10 caractères.');

    await boite.getByRole('button', { name: 'Annuler' }).click();
    await expect(page.locator('.card', { hasText: 'Validée' }).first()).toBeVisible();
  });

  test.describe('DRH', () => {
    test.use({ profil: 'drh' });

    test('G3 — le DRH ne peut pas rouvrir une période validée', async ({ page }) => {
      await ouvrirPeriode(page, /Validée/);
      await expect(page.getByRole('button', { name: 'Rouvrir' })).toHaveCount(0);
    });
  });
});

test.describe('Préparation, calcul et validation de la période ouverte', () => {
  test.describe.configure({ mode: 'serial' });
  test.slow();

  test('G4 — le Responsable RH saisit un élément variable mais ne calcule ni ne valide', async ({ browser }) => {
    const rh = await pageDe(browser, 'rh');
    await ouvrirPeriode(rh, /Ouverte|Calculée/);

    await rh.getByRole('button', { name: 'Saisir un élément' }).click();
    const modale = await attendreModale(rh, 'Saisir un élément variable');
    await modale.getByRole('combobox', { name: 'Salarié', exact: true }).selectOption({ index: 1 });
    await modale.getByRole('combobox', { name: 'Rubrique' }).selectOption({ index: 1 });
    await modale.getByRole('spinbutton', { name: 'Montant (FCFA)' }).fill('15000');
    await modale.getByRole('button', { name: 'Enregistrer' }).click();
    await attendreToast(rh, 'Saisie enregistrée.');

    await expect(rh.getByRole('button', { name: 'Calculer la paie' })).toHaveCount(0);
    await expect(rh.getByRole('button', { name: 'Valider définitivement' })).toHaveCount(0);
    await rh.context().close();
  });

  test('G5 — le DRH calcule les bulletins de la période', async ({ browser }) => {
    const drh = await pageDe(browser, 'drh');
    await ouvrirPeriode(drh, /Ouverte|Calculée/);

    await drh.getByRole('button', { name: 'Calculer la paie' }).click();
    await repondreConfirmation(drh, 'Oui, calculer');
    await attendreToast(drh, /bulletin\(s\) calculé\(s\)/);
    await expect(drh.locator('.badge', { hasText: 'Calculée' }).first()).toBeVisible();
    await drh.context().close();
  });

  test('G6 — le DRH valide définitivement : la période devient figée', async ({ browser }) => {
    const drh = await pageDe(browser, 'drh');
    await ouvrirPeriode(drh, /Calculée/);

    await drh.getByRole('button', { name: 'Valider définitivement' }).click();
    const modale = await attendreModale(drh, 'Validation définitive');
    // Case de confirmation obligatoire (required) : sans elle le formulaire n'est pas soumis
    await modale.getByRole('checkbox', { name: /Je confirme la validation définitive/ }).check();
    await modale.getByRole('button', { name: 'Valider définitivement' }).click();

    await attendreToast(drh, /validée/i);
    await expect(drh.getByRole('button', { name: 'Calculer la paie' })).toHaveCount(0);
    await expect(drh.getByRole('button', { name: 'Saisir un élément' })).toHaveCount(0);
    await drh.context().close();
  });
});
