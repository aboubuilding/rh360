import type { Page } from '@playwright/test';
import { test, expect, unique } from './support/fixtures';
import { attendreModale, attendreToast, erreurDuChamp, erreurSous } from './support/ui';

/*
| C. Dossiers RH — CDC §4 2.1 (salariés), 2.2 (création en cinq étapes),
| 2.5 (documents), §6 (données sensibles, pièces justificatives).
| Salarié de démonstration : DOSSOU Kofi (MAT-2026-0001), avec CNSS et RIB.
*/

const MATRICULE_DEMO = 'MAT-2026-0001';
const RIB_DEMO = 'TG53 0001 0002 0003 0004 5678';

/** Ouvre la fiche d'un salarié depuis l'annuaire (recherche puis action « Fiche »). */
async function ouvrirFiche(page: Page, matricule = MATRICULE_DEMO): Promise<void> {
  await page.goto('/salaries');
  await page.getByRole('searchbox', { name: 'Rechercher un salarié' }).fill(matricule);
  await page.getByRole('button', { name: 'Filtrer' }).click();
  const ligne = page.getByRole('row').filter({ hasText: matricule });
  await ligne.locator('[data-bs-toggle="dropdown"]').click();
  await ligne.getByRole('link', { name: 'Fiche' }).click();
  await expect(page).toHaveURL(/\/salaries\/\d+$/);
}

/** Abandonne le brouillon de création éventuellement en cours pour repartir de l'étape 1. */
async function repartirDeZero(page: Page): Promise<void> {
  await page.goto('/salaries/creer');
  page.once('dialog', (d) => d.accept());
  await page.getByRole('button', { name: 'Abandonner le brouillon' }).click();
  await expect(page).toHaveURL(/\/salaries$/);
  await page.goto('/salaries/creer');
  await expect(page).toHaveURL(/\/salaries\/creer\/etape\/1$/);
}

async function continuer(page: Page): Promise<void> {
  await page.getByRole('button', { name: /Enregistrer et (continuer|vérifier)/ }).click();
}

test.describe('Annuaire des salariés', () => {
  test.use({ profil: 'rh' });

  test('C1 — recherche un salarié par nom ou matricule', async ({ page }) => {
    await page.goto('/salaries');
    await page.getByRole('searchbox', { name: 'Rechercher un salarié' }).fill('DOSSOU');
    await page.getByRole('button', { name: 'Filtrer' }).click();

    const lignes = page.locator('tbody tr');
    await expect(lignes).toHaveCount(1);
    await expect(lignes.first()).toContainText(MATRICULE_DEMO);
  });

  test('C2 — filtre les dossiers à compléter', async ({ page }) => {
    await page.goto('/salaries');
    await page.getByRole('combobox', { name: 'Complétude du dossier' }).selectOption('incomplets');
    await page.getByRole('button', { name: 'Filtrer' }).click();

    await expect(page).toHaveURL(/completude=incomplets/);
    for (const badge of await page.locator('tbody tr td .badge').allTextContents()) {
      expect(badge).not.toMatch(/^Complet$/);
    }
  });
});

test.describe('Assistant de création (5 étapes)', () => {
  test.use({ profil: 'rh' });
  test.describe.configure({ mode: 'serial' });

  test('C3 — crée un salarié en cinq étapes, avec affectation et classification', async ({ page }) => {
    await repartirDeZero(page);
    const nom = unique('E2E').toUpperCase();

    await page.getByRole('textbox', { name: 'Nom', exact: true }).fill(nom);
    await page.getByRole('textbox', { name: 'Prénoms', exact: true }).fill('Assistant');
    await page.getByLabel('Sexe').selectOption('F');
    await continuer(page);

    await expect(page).toHaveURL(/etape\/2$/);
    await continuer(page);
    await expect(page).toHaveURL(/etape\/3$/);
    await continuer(page);
    await expect(page).toHaveURL(/etape\/4$/);
    await continuer(page);

    await expect(page).toHaveURL(/etape\/5$/);
    await page.getByLabel('Date d\'embauche').fill('2026-01-05');
    await page.getByLabel('Date de prise de service').fill('2026-01-12');
    await page.getByLabel('Structure d\'affectation').selectOption({ index: 1 });
    await page.getByLabel('Poste', { exact: true }).selectOption({ index: 1 });
    await page.getByLabel(/Classification/).selectOption({ index: 1 });
    await continuer(page);

    await expect(page).toHaveURL(/recapitulatif$/);
    await expect(page.getByText(nom)).toBeVisible();
    await page.getByRole('button', { name: 'Valider la création' }).click();

    await attendreToast(page, 'Salarié créé avec succès.');
    await expect(page).toHaveURL(/\/salaries\/\d+$/);
    await page.getByRole('tab', { name: 'Affectations' }).click();
    await expect(page.locator('#tab-affectations')).toContainText(/\d{2}\/\d{2}\/2026/);
  });

  test('C4 — refuse un nom vide (validation serveur)', async ({ page }) => {
    await repartirDeZero(page);

    // Espaces : l'attribut required du navigateur est satisfait, Laravel les supprime et refuse
    await page.getByRole('textbox', { name: 'Nom', exact: true }).fill('   ');
    await page.getByRole('textbox', { name: 'Prénoms', exact: true }).fill('Sans nom');
    await continuer(page);

    await expect(page).toHaveURL(/etape\/1$/);
    await expect(erreurSous(page.getByRole('textbox', { name: 'Nom', exact: true }))).toHaveText('Le nom est obligatoire.');
  });

  test('C5 — refuse une prise de service antérieure à l\'embauche', async ({ page }) => {
    await repartirDeZero(page);
    await page.getByRole('textbox', { name: 'Nom', exact: true }).fill('DATES');
    await page.getByRole('textbox', { name: 'Prénoms', exact: true }).fill('Incohérentes');
    for (let i = 0; i < 4; i++) await continuer(page);

    await page.getByLabel('Date d\'embauche').fill('2026-03-01');
    await page.getByLabel('Date de prise de service').fill('2026-02-01');
    await continuer(page);

    await expect(page).toHaveURL(/etape\/5$/);
    await expect(erreurDuChamp(page, 'Date de prise de service'))
      .toHaveText('La date de prise de service doit être postérieure ou égale à la date d\'embauche.');
  });

  test('C6 — reprend le brouillon à l\'étape atteinte, puis l\'abandonne', async ({ page }) => {
    await repartirDeZero(page);
    await page.getByRole('textbox', { name: 'Nom', exact: true }).fill('BROUILLON');
    await page.getByRole('textbox', { name: 'Prénoms', exact: true }).fill('Repris');
    await continuer(page);
    await expect(page).toHaveURL(/etape\/2$/);

    // L'utilisateur quitte l'assistant puis revient par le menu
    await page.goto('/salaries');
    await page.getByRole('navigation', { name: 'Menu principal' }).getByRole('button', { name: 'Dossiers RH' }).click();
    await page.getByRole('menuitem', { name: 'Nouveau salarié' }).click();

    await expect(page).toHaveURL(/etape\/2$/);
    await attendreToast(page, /Brouillon de création repris/);
    await page.getByRole('link', { name: /1\. Identité/ }).click();
    await expect(page.getByRole('textbox', { name: 'Nom', exact: true })).toHaveValue('BROUILLON');

    page.once('dialog', (d) => d.accept());
    await page.getByRole('button', { name: 'Abandonner le brouillon' }).click();
    await attendreToast(page, 'Création annulée.');
  });
});

test.describe('Fiche salarié', () => {
  test.use({ profil: 'rh' });

  test('C7 — ajoute, archive puis restaure un membre du foyer', async ({ page }) => {
    const prenom = unique('Enfant');
    await ouvrirFiche(page);
    await page.getByRole('tab', { name: 'Famille' }).click();
    await page.locator('#tab-famille').getByRole('button', { name: 'Ajouter' }).click();

    const modale = await attendreModale(page, 'Ajouter un membre');
    await modale.getByLabel('Lien de parenté').selectOption('Enfant');
    await modale.getByRole('textbox', { name: 'Nom', exact: true }).fill('DOSSOU');
    await modale.getByRole('textbox', { name: 'Prénoms', exact: true }).fill(prenom);
    await modale.getByLabel('Date de naissance').fill('2019-04-02');
    await modale.getByRole('button', { name: 'Enregistrer' }).click();
    await attendreToast(page, /Membre/);

    await page.getByRole('tab', { name: 'Famille' }).click();
    const ligne = page.getByRole('row').filter({ hasText: prenom });
    await expect(ligne).toContainText('Actif');

    await ligne.getByRole('button', { name: `Archiver DOSSOU ${prenom}` }).click();
    await attendreToast(page, 'Membre archivé.');
    await page.getByRole('tab', { name: 'Famille' }).click();
    await expect(page.getByRole('row').filter({ hasText: prenom })).toContainText('Archivé');

    await page.getByRole('row').filter({ hasText: prenom })
      .getByRole('button', { name: `Restaurer DOSSOU ${prenom}` }).click();
    await attendreToast(page, 'Membre restauré.');
    await page.getByRole('tab', { name: 'Famille' }).click();
    await expect(page.getByRole('row').filter({ hasText: prenom })).toContainText('Actif');
  });

  test('C8 — ajoute un document puis l\'archive avec un motif obligatoire', async ({ page }) => {
    await ouvrirFiche(page);
    await page.getByRole('tab', { name: /Documents/ }).click();
    await page.locator('#tab-documents').getByRole('button', { name: 'Ajouter' }).click();

    const modale = await attendreModale(page, 'Ajouter un document');
    await modale.getByLabel('Type de document').selectOption('Attestation');
    await modale.getByLabel('Fichier').setInputFiles({
      name: 'attestation.pdf', mimeType: 'application/pdf', buffer: Buffer.from('%PDF-1.4\n% attestation E2E\n'),
    });
    await modale.getByLabel('Observations').fill('Attestation E2E');
    await modale.getByRole('button', { name: 'Enregistrer' }).click();
    await attendreToast(page, /Document/);

    await page.getByRole('tab', { name: /Documents/ }).click();
    const ligne = page.getByRole('row').filter({ hasText: 'Attestation E2E' });
    await expect(ligne).toContainText('Actif');

    // Motif trop court : refusé par la boîte de saisie
    await ligne.getByRole('button', { name: 'Archiver Attestation' }).click();
    await page.locator('.swal2-input').fill('abc');
    await page.locator('.swal2-popup').getByRole('button', { name: 'Archiver' }).click();
    await expect(page.locator('.swal2-validation-message')).toHaveText('Le motif doit contenir au moins 5 caractères.');

    await page.locator('.swal2-input').fill('Remplacée par une attestation à jour');
    await page.locator('.swal2-popup').getByRole('button', { name: 'Archiver' }).click();
    await attendreToast(page, 'Document archivé.');
    await page.getByRole('tab', { name: /Documents/ }).click();
    await expect(page.getByRole('row').filter({ hasText: 'Attestation E2E' })).toContainText('Archivé');
  });

  test('C9 — refuse un fichier de plus de 8 Mo', async ({ page }) => {
    await ouvrirFiche(page);
    await page.getByRole('tab', { name: /Documents/ }).click();
    await page.locator('#tab-documents').getByRole('button', { name: 'Ajouter' }).click();

    const modale = await attendreModale(page, 'Ajouter un document');
    await modale.getByLabel('Type de document').selectOption('Diplôme');
    await modale.getByLabel('Fichier').setInputFiles({
      name: 'trop-lourd.pdf', mimeType: 'application/pdf', // En-tête PDF réel : seul le contrôle de taille (8 Mo) doit échouer
      buffer: Buffer.concat([Buffer.from('%PDF-1.4\n'), Buffer.alloc(9 * 1024 * 1024, ' ')]),
    });
    await modale.getByRole('button', { name: 'Enregistrer' }).click();

    await expect(erreurDuChamp(modale, 'Fichier')).toHaveText('Le fichier ne doit pas dépasser 8 Mo.');
    await expect(modale).toBeVisible();
  });

  test('C10 — ouvre un document uniquement par l\'accès protégé', async ({ page }) => {
    await ouvrirFiche(page);
    await page.getByRole('tab', { name: /Documents/ }).click();
    const lien = page.locator('#tab-documents').getByRole('link', { name: /^Ouvrir/ }).first();
    const href = await lien.getAttribute('href');

    expect(href).toMatch(/\/salaries\/\d+\/documents\/\d+\/voir$/);
    expect(href).not.toContain('/storage/');
    expect((await page.request.get(href!)).status()).toBe(200);
  });
});

test.describe('Données sensibles (§6)', () => {
  test('C11 — le Super administrateur voit les onglets social et bancaire', async ({ page }) => {
    await ouvrirFiche(page);
    await expect(page.getByRole('tab', { name: 'Social & santé' })).toBeVisible();
    await page.getByRole('tab', { name: 'Bancaire' }).click();
    await expect(page.getByText(RIB_DEMO)).toBeVisible();
  });

  test.describe('Responsable RH', () => {
    test.use({ profil: 'rh' });

    test('C12 — voit le social mais pas le bancaire', async ({ page }) => {
      await ouvrirFiche(page);
      await expect(page.getByRole('tab', { name: 'Social & santé' })).toBeVisible();
      await expect(page.getByRole('tab', { name: 'Bancaire' })).toHaveCount(0);
      await expect(page.locator('body')).not.toContainText(RIB_DEMO);
    });
  });

  test.describe('Direction générale', () => {
    test.use({ profil: 'direction' });

    test('C13 — consulte le dossier sans aucune donnée sensible ni action de gestion', async ({ page }) => {
      await ouvrirFiche(page);
      await expect(page.getByRole('tab', { name: 'Social & santé' })).toHaveCount(0);
      await expect(page.getByRole('tab', { name: 'Bancaire' })).toHaveCount(0);
      await expect(page.locator('body')).not.toContainText(RIB_DEMO);

      await page.getByRole('tab', { name: 'Famille' }).click();
      await expect(page.locator('#tab-famille').getByRole('button', { name: 'Ajouter' })).toHaveCount(0);
    });
  });
});
