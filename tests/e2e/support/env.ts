import { execFileSync } from 'node:child_process';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

/**
 * Environnement des tests E2E : serveur `php artisan serve` dédié (port 8001)
 * branché sur la base MySQL `expert_rh_360_e2e`. La base de développement
 * et le site rh360.test ne sont jamais utilisés par ces tests.
 */
export const RACINE = path.resolve(__dirname, '..', '..', '..');
export const PORT = 8001;
export const BASE_URL = `http://127.0.0.1:${PORT}`;

/** PHP 8.3 de Laragon (surcharge possible via la variable E2E_PHP). */
export const PHP = process.env.E2E_PHP ?? 'C:\\laragon\\bin\\php\\php-8.3.30-Win32-vs16-x64\\php.exe';

export const BASE_E2E = 'expert_rh_360_e2e';

/** Variables transmises au serveur et aux commandes artisan : elles priment sur le .env. */
export const ENV_E2E: Record<string, string> = {
  APP_URL: BASE_URL,
  APP_DEBUG: 'true',
  DB_CONNECTION: 'mysql',
  DB_HOST: process.env.E2E_DB_HOST ?? '127.0.0.1',
  DB_PORT: process.env.E2E_DB_PORT ?? '3306',
  DB_DATABASE: BASE_E2E,
  DB_USERNAME: process.env.E2E_DB_USERNAME ?? 'root',
  DB_PASSWORD: process.env.E2E_DB_PASSWORD ?? '',
  SESSION_DRIVER: 'database',
  // Cache (dont le limiteur de tentatives de connexion) en base : remis à zéro avec la base
  CACHE_STORE: 'database',
  QUEUE_CONNECTION: 'sync',
  MAIL_MAILER: 'array',
};

/** Exécute une commande artisan sur la base E2E (jamais sur une autre base). */
export function artisan(...args: string[]): string {
  if (!ENV_E2E.DB_DATABASE.endsWith('_e2e')) {
    throw new Error(`Refus d'exécuter artisan sur la base « ${ENV_E2E.DB_DATABASE} » (suffixe _e2e attendu).`);
  }
  return execFileSync(PHP, ['artisan', ...args], {
    cwd: RACINE,
    env: { ...process.env, ...ENV_E2E },
    encoding: 'utf8',
    stdio: ['ignore', 'pipe', 'pipe'],
  });
}
