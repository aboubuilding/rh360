import { defineConfig, devices } from '@playwright/test';
import { BASE_URL, ENV_E2E, PHP, PORT } from './tests/e2e/support/env';

/**
 * Tests E2E EXPERT RH 360 (tests/e2e).
 * - Serveur dédié : `php artisan serve` (PHP 8.3 Laragon) sur le port 8001, base expert_rh_360_e2e.
 * - global-setup : réinitialisation de la base + une session enregistrée par profil.
 * Lancement : npx playwright test   (rapport : npx playwright show-report)
 */
export default defineConfig({
  testDir: './tests/e2e',
  testMatch: /.*\.spec\.ts$/,
  globalSetup: './tests/e2e/global-setup.ts',
  outputDir: './tests/e2e/.resultats',

  // Le serveur PHP intégré traite une requête à la fois sous Windows : exécution séquentielle.
  workers: 1,
  fullyParallel: false,
  retries: process.env.CI ? 1 : 0,
  timeout: 45_000,
  expect: { timeout: 10_000 },

  reporter: [['list'], ['html', { outputFolder: './tests/e2e/.rapport', open: 'never' }]],

  use: {
    baseURL: BASE_URL,
    locale: 'fr-FR',
    timezoneId: 'Africa/Lome',
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
    actionTimeout: 10_000,
    navigationTimeout: 20_000,
  },

  projects: [{ name: 'chromium', use: { ...devices['Desktop Chrome'] } }],

  webServer: {
    // --no-reload : transmet toutes les variables d'environnement (dont DB_DATABASE) au serveur
    command: `"${PHP}" artisan serve --host=127.0.0.1 --port=${PORT} --no-reload`,
    // /up : route de santé Laravel, sans base de données (la base E2E est migrée par global-setup)
    url: `${BASE_URL}/up`,
    env: ENV_E2E,
    reuseExistingServer: !process.env.CI,
    timeout: 60_000,
  },
});
