import { expect, type Page } from '@playwright/test';
import { COMPTES, type Profil } from './comptes';

/** Remplit et soumet le formulaire de connexion (AJAX), sans attendre le résultat. */
export async function soumettreConnexion(page: Page, identifiant: string, motDePasse: string): Promise<void> {
  await page.goto('/connexion');
  await page.getByLabel('Identifiant').fill(identifiant);
  // Le libellé se termine par « * » : distingue le champ du bouton « Afficher le mot de passe »
  await page.getByLabel(/Mot de passe\s*\*/).fill(motDePasse);
  await page.getByRole('button', { name: 'Connexion' }).click();
}

/** Connecte un profil par le vrai formulaire et attend l'arrivée sur le tableau de bord. */
export async function seConnecter(page: Page, profil: Profil): Promise<void> {
  const { identifiant, motDePasse } = COMPTES[profil];
  await soumettreConnexion(page, identifiant, motDePasse);
  await expect(page).toHaveURL(/\/tableau-de-bord$/, { timeout: 20_000 });
}
