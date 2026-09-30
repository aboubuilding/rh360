import { test, expect } from './support/fixtures';
import { COMPTES } from './support/comptes';
import { seConnecter, soumettreConnexion } from './support/connexion';

/*
| A. Authentification — CDC §3, §6 (sessions), §7.5 (mots de passe).
| Formulaire AJAX : les erreurs s'affichent dans un bandeau (role=alert) et sous les champs.
*/

test.use({ profil: null });

test.describe('Connexion', () => {
  test('A1 — connecte un utilisateur valide et ouvre le tableau de bord', async ({ page }) => {
    await seConnecter(page, 'rh');

    await expect(page.getByRole('heading', { level: 1, name: /Tableau de bord/ })).toBeVisible();
    await expect(page.getByRole('button', { name: /Mon compte : Responsable RH E2E/ })).toBeVisible();
  });

  test('A2 — signale les champs obligatoires sans appeler le serveur', async ({ page }) => {
    await page.goto('/connexion');
    let appels = 0;
    page.on('request', (r) => { if (r.method() === 'POST' && r.url().endsWith('/connexion')) appels++; });

    await page.getByRole('button', { name: 'Connexion' }).click();

    await expect(page.getByText("L'identifiant est obligatoire")).toBeVisible();
    expect(appels).toBe(0);
  });

  test('A3 — refuse un mauvais mot de passe', async ({ page }) => {
    await soumettreConnexion(page, COMPTES.rh.identifiant, 'mauvais-mot-de-passe');

    await expect(page.getByRole('alert').filter({ hasText: 'Identifiant ou mot de passe incorrect' }).first())
      .toBeVisible();
    await expect(page).toHaveURL(/\/connexion$/);
  });

  test('A4 — ne révèle pas si un identifiant existe', async ({ page }) => {
    await soumettreConnexion(page, 'compte-inexistant-e2e', 'peu-importe');

    // Même message que pour un mauvais mot de passe
    await expect(page.locator('#alert-banner')).toContainText('Identifiant ou mot de passe incorrect');
    await expect(page.locator('#alert-banner')).not.toContainText(/introuvable|aucun compte/i);
  });

  test('A5 — bloque après 5 tentatives échouées', async ({ page }) => {
    await page.goto('/connexion');
    await page.getByLabel('Identifiant').fill(COMPTES.auditeur.identifiant);

    for (let i = 0; i < 5; i++) {
      await page.getByLabel(/Mot de passe\s*\*/).fill(`mauvais-${i}`);
      await Promise.all([
        page.waitForResponse((r) => r.url().endsWith('/connexion') && r.status() === 401),
        page.getByRole('button', { name: 'Connexion' }).click(),
      ]);
    }

    // Même le bon mot de passe est refusé tant que la limite est atteinte
    await page.getByLabel(/Mot de passe\s*\*/).fill(COMPTES.auditeur.motDePasse);
    await page.getByRole('button', { name: 'Connexion' }).click();
    await expect(page.locator('#alert-banner')).toContainText('Trop de tentatives');
    await expect(page).toHaveURL(/\/connexion$/);
  });

  test('A6 — redirige un visiteur vers la connexion', async ({ page }) => {
    await page.goto('/salaries');
    await expect(page).toHaveURL(/\/connexion$/);
  });
});

test.describe('Déconnexion', () => {
  test('A7 — déconnecte l\'utilisateur depuis le menu du compte et protège de nouveau les pages', async ({ page }) => {
    // Connexion propre au test : la déconnexion n'invalide pas les sessions partagées des autres specs
    await seConnecter(page, 'manager');

    await page.getByRole('button', { name: /Mon compte/ }).click();
    await page.getByRole('menuitem', { name: 'Se déconnecter' }).click();
    await expect(page).toHaveURL(/\/connexion$/);

    await page.goto('/tableau-de-bord');
    await expect(page).toHaveURL(/\/connexion$/);
  });
});

test.describe('Page d\'accueil publique', () => {
  test('A8 — un visiteur voit la page d\'accueil ; la connexion ne s\'affiche que via le lien', async ({ page }) => {
    await page.goto('/');
    await expect(page).toHaveURL(/\/$/);
    await expect(page.getByRole('heading', { level: 1 })).toContainText('Pilotez vos équipes');
    await expect(page.getByRole('textbox', { name: /Identifiant/ })).toHaveCount(0);

    await page.getByRole('navigation', { name: 'Navigation principale' }).getByRole('link', { name: 'Connexion' }).click();
    await expect(page).toHaveURL(/\/connexion$/);
    await expect(page.getByRole('heading', { name: 'Connexion' })).toBeVisible();

    await page.getByRole('link', { name: 'Retour à l\'accueil' }).click();
    await expect(page).toHaveURL(/\/$/);
  });
});

test.describe('Page d\'accueil (utilisateur connecté)', () => {
  test.use({ profil: 'rh' });

  test('A9 — un utilisateur connecté est envoyé directement sur son tableau de bord', async ({ page }) => {
    await page.goto('/');
    await expect(page).toHaveURL(/\/tableau-de-bord$/);
  });
});
