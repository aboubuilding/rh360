import { test, expect, unique } from './support/fixtures';
import { soumettreConnexion } from './support/connexion';
import { BASE_URL } from './support/env';
import { attendreModale, attendreToast, erreurDuChamp, ouvrirActionsDeLaLigne } from './support/ui';

/*
| J. Administration — CDC §4 8.1 (entreprise), 8.4 (utilisateurs et permissions),
| 8.5 (journal d'audit), §6 (permissions à deux niveaux, journal d'audit).
*/

const MOT_DE_PASSE_VALIDE = 'Nouveau@2026';

/** Crée un utilisateur depuis la modale de la liste et renvoie son identifiant. */
async function creerUtilisateur(page: import('@playwright/test').Page, role = 'Manager'): Promise<string> {
  const identifiant = unique('u').toLowerCase();
  await page.goto('/admin/utilisateurs');
  await page.getByRole('button', { name: 'Nouvel utilisateur' }).click();

  const modale = await attendreModale(page, 'Nouvel utilisateur');
  await modale.getByLabel('Nom complet').fill(`Utilisateur ${identifiant}`);
  await modale.getByLabel('Identifiant de connexion').fill(identifiant);
  await modale.getByLabel('Rôle').selectOption({ label: role });
  await modale.getByLabel('Mot de passe', { exact: true }).fill(MOT_DE_PASSE_VALIDE);
  await modale.getByLabel('Confirmer le mot de passe').fill(MOT_DE_PASSE_VALIDE);
  await modale.getByRole('button', { name: 'Enregistrer' }).click();

  await attendreToast(page, 'Utilisateur créé avec succès.');
  await expect(page.getByRole('row').filter({ hasText: identifiant })).toBeVisible();
  return identifiant;
}

test.describe('Utilisateurs (Super administrateur)', () => {
  test('J1 — crée un utilisateur depuis la modale et l\'affiche dans la liste', async ({ page }) => {
    const identifiant = await creerUtilisateur(page, 'Responsable RH');

    const ligne = page.getByRole('row').filter({ hasText: identifiant });
    await expect(ligne).toContainText('Responsable RH');
    await expect(ligne).toContainText('Actif');
  });

  test('J2 — refuse un identifiant déjà utilisé et garde la modale ouverte', async ({ page }) => {
    await page.goto('/admin/utilisateurs');
    await page.getByRole('button', { name: 'Nouvel utilisateur' }).click();
    const modale = await attendreModale(page, 'Nouvel utilisateur');

    await modale.getByLabel('Nom complet').fill('Doublon');
    await modale.getByLabel('Identifiant de connexion').fill('e2e_rh');
    await modale.getByLabel('Rôle').selectOption({ label: 'Manager' });
    await modale.getByLabel('Mot de passe', { exact: true }).fill(MOT_DE_PASSE_VALIDE);
    await modale.getByLabel('Confirmer le mot de passe').fill(MOT_DE_PASSE_VALIDE);
    await modale.getByRole('button', { name: 'Enregistrer' }).click();

    await expect(erreurDuChamp(modale, 'Identifiant de connexion'))
      .toHaveText('Cet identifiant est déjà utilisé dans votre entreprise.');
    await attendreToast(page, 'Veuillez corriger les erreurs.', 'erreur');
    await expect(modale).toBeVisible();
  });

  test('J3 — impose un mot de passe robuste et confirmé', async ({ page }) => {
    await page.goto('/admin/utilisateurs');
    await page.getByRole('button', { name: 'Nouvel utilisateur' }).click();
    const modale = await attendreModale(page, 'Nouvel utilisateur');

    await modale.getByLabel('Nom complet').fill('Mot de passe faible');
    await modale.getByLabel('Identifiant de connexion').fill(unique('faible').toLowerCase());
    await modale.getByLabel('Rôle').selectOption({ label: 'Manager' });
    await modale.getByLabel('Mot de passe', { exact: true }).fill('faible');
    await modale.getByLabel('Confirmer le mot de passe').fill('different');
    await modale.getByRole('button', { name: 'Enregistrer' }).click();

    await expect(erreurDuChamp(modale, 'Mot de passe', { exact: true })).toBeVisible();
    await expect(modale).toBeVisible();
  });

  test('J4 — désactive un compte, qui ne peut alors plus se connecter', async ({ page, browser }) => {
    const identifiant = await creerUtilisateur(page);

    const menu = await ouvrirActionsDeLaLigne(page, identifiant);
    await menu.getByRole('button', { name: 'Désactiver' }).click();
    await attendreToast(page, 'Statut de l\'utilisateur modifié.');
    await expect(page.getByRole('row').filter({ hasText: identifiant })).toContainText('Désactivé');

    // Tentative de connexion avec le compte désactivé, dans une session vierge
    const visiteur = await browser.newContext({ baseURL: BASE_URL, storageState: undefined });
    const pageVisiteur = await visiteur.newPage();
    await soumettreConnexion(pageVisiteur, identifiant, MOT_DE_PASSE_VALIDE);
    await expect(pageVisiteur.getByRole('dialog').filter({ hasText: 'Compte désactivé' })).toBeVisible();
    await expect(pageVisiteur).toHaveURL(/\/connexion$/);
    await visiteur.close();
  });
});

test.describe('Utilisateurs (Administrateur)', () => {
  test.use({ profil: 'admin' });

  test('J5 — l\'Administrateur gère les comptes mais pas les permissions', async ({ page }) => {
    await page.goto('/admin/utilisateurs');
    await expect(page.getByRole('button', { name: 'Nouvel utilisateur' })).toBeVisible();

    const menu = await ouvrirActionsDeLaLigne(page, 'e2e_manager');
    await expect(menu.getByRole('link', { name: 'Exceptions' })).toHaveCount(0);
  });
});

test.describe('Permissions (DRH)', () => {
  test.use({ profil: 'drh' });

  test('J6 — accorde une exception individuelle qui prend effet immédiatement', async ({ page, browser }) => {
    // Le Manager ne voit pas la paie par défaut
    const manager = await browser.newContext({
      baseURL: BASE_URL,
      storageState: 'tests/e2e/.auth/manager.json',
    });
    const pageManager = await manager.newPage();
    expect((await pageManager.goto('/paie/periodes'))?.status()).toBe(403);

    // Le DRH lui accorde paie.view par exception
    await page.goto('/admin/utilisateurs');
    const menu = await ouvrirActionsDeLaLigne(page, 'e2e_manager');
    await menu.getByRole('link', { name: 'Exceptions' }).click();
    await page.getByRole('checkbox', { name: 'Accorder paie.view' }).check();
    await page.getByRole('button', { name: 'Enregistrer les exceptions' }).click();
    await attendreToast(page, 'Exceptions individuelles mises à jour.');

    try {
      // Effet immédiat, sans reconnexion
      expect((await pageManager.goto('/paie/periodes'))?.status()).toBe(200);
    } finally {
      // Remise en état : aucune exception pour le Manager
      await page.getByRole('checkbox', { name: 'Accorder paie.view' }).uncheck();
      await page.getByRole('button', { name: 'Enregistrer les exceptions' }).click();
      await attendreToast(page, 'Exceptions individuelles mises à jour.');
      await manager.close();
    }
  });

  test('J7 — modifie la matrice d\'un rôle avec effet immédiat pour ses utilisateurs', async ({ page, browser }) => {
    const auditeur = await browser.newContext({
      baseURL: BASE_URL,
      storageState: 'tests/e2e/.auth/auditeur.json',
    });
    const pageAuditeur = await auditeur.newPage();
    expect((await pageAuditeur.goto('/admin/audit'))?.status()).toBe(200);

    await page.goto('/admin/permissions/matrice');
    const formulaire = page.getByRole('form', { name: 'Permissions du rôle Auditeur' });
    const caseAudit = formulaire.getByRole('checkbox', { name: 'admin.audit.view' });

    await caseAudit.uncheck();
    await formulaire.getByRole('button', { name: 'Enregistrer' }).click();
    await attendreToast(page, /Matrice mise à jour/);

    try {
      expect((await pageAuditeur.goto('/admin/audit'))?.status()).toBe(403);
    } finally {
      await page.getByRole('form', { name: 'Permissions du rôle Auditeur' })
        .getByRole('checkbox', { name: 'admin.audit.view' }).check();
      await page.getByRole('form', { name: 'Permissions du rôle Auditeur' })
        .getByRole('button', { name: 'Enregistrer' }).click();
      await attendreToast(page, /Matrice mise à jour/);
      expect((await pageAuditeur.goto('/admin/audit'))?.status()).toBe(200);
      await auditeur.close();
    }
  });
});

test.describe('Entreprise et journal d\'audit', () => {
  test('J8 — modifie la fiche entreprise et trace la modification dans le journal', async ({ page }) => {
    const secteur = unique('Secteur');
    await page.goto('/admin/entreprise/edit');
    await page.getByLabel('Secteur').fill(secteur);
    await page.getByRole('button', { name: 'Enregistrer' }).click();

    await attendreToast(page, 'Fiche entreprise mise à jour.');
    await page.goto('/admin/entreprise/edit');
    await expect(page.getByLabel('Secteur')).toHaveValue(secteur);

    await page.goto('/admin/audit?action=updated&entite=Entreprise');
    await expect(page.getByRole('row').filter({ hasText: 'Entreprise' }).first()).toBeVisible();
  });

  test('J9 — refuse une raison sociale vide', async ({ page }) => {
    await page.goto('/admin/entreprise/edit');
    let envois = 0;
    page.on('request', (r) => { if (r.method() === 'POST' && r.url().includes('/admin/entreprise')) envois++; });

    const champ = page.getByLabel('Raison sociale');
    await champ.fill('');
    await page.getByRole('button', { name: 'Enregistrer' }).click();

    // Champ obligatoire (attribut required) : le navigateur bloque l'envoi
    expect(await champ.evaluate((el: HTMLInputElement) => el.validity.valueMissing)).toBe(true);
    expect(envois).toBe(0);
    await expect(page).toHaveURL(/\/admin\/entreprise\/edit$/);
  });

  test('J10 — refuse un logo qui n\'est pas une image', async ({ page }) => {
    await page.goto('/admin/entreprise/edit');
    await page.getByLabel('Changer le logo').setInputFiles({
      name: 'logo.pdf', mimeType: 'application/pdf', buffer: Buffer.from('%PDF-1.4 faux logo'),
    });
    await page.getByRole('button', { name: 'Enregistrer' }).click();

    await expect(erreurDuChamp(page, 'Changer le logo')).toBeVisible();
  });
});

test.describe('Journal d\'audit (Auditeur)', () => {
  test.use({ profil: 'auditeur' });

  test('J11 — l\'Auditeur consulte et filtre le journal en lecture seule', async ({ page }) => {
    await page.goto('/admin/audit');
    await expect(page.getByRole('heading', { level: 1, name: /Journal d'audit/ })).toBeVisible();

    await page.locator('select[name="action"]').selectOption({ index: 1 });
    await page.getByRole('button', { name: 'Filtrer' }).click();
    await expect(page).toHaveURL(/action=/);
  });
});
