import { chromium, type FullConfig } from '@playwright/test';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
import { artisan, BASE_URL } from './support/env';
import { PROFILS, sessionDe } from './support/comptes';
import { seConnecter } from './support/connexion';

/**
 * 1. Remet la base E2E dans l'état de démonstration (migrate:fresh --seed + comptes E2E).
 * 2. Connecte chaque profil une seule fois par le vrai formulaire et sauvegarde ses cookies :
 *    les specs réutilisent ces sessions sans repasser par la connexion.
 */
export default async function globalSetup(_config: FullConfig): Promise<void> {
  if (!process.env.E2E_SANS_RESET) {
    console.log('[e2e] Réinitialisation de la base expert_rh_360_e2e…');
    artisan('migrate:fresh', '--seed', '--force');
    artisan('db:seed', '--class=E2eSeeder', '--force');
  }

  fs.mkdirSync(path.join(__dirname, '.auth'), { recursive: true });

  const navigateur = await chromium.launch();
  try {
    for (const profil of PROFILS) {
      const contexte = await navigateur.newContext({ baseURL: BASE_URL });
      const page = await contexte.newPage();
      await seConnecter(page, profil);

      await contexte.storageState({ path: sessionDe(profil) });
      await contexte.close();
    }
  } finally {
    await navigateur.close();
  }
}
