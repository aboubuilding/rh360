import { test as base, expect } from '@playwright/test';
import { sessionDe, type Profil } from './comptes';

type Options = {
  /** Profil connecté pour le test (session enregistrée par global-setup) ; null = visiteur anonyme. */
  profil: Profil | null;
};

/**
 * `test` à utiliser dans toutes les specs :
 *   test.use({ profil: 'rh' });        // connecté en Responsable RH
 *   test.use({ profil: null });        // non connecté
 * Par défaut : Super administrateur.
 */
export const test = base.extend<Options>({
  profil: ['superadmin', { option: true }],
  storageState: async ({ profil }, use) => {
    await use(profil ? sessionDe(profil) : { cookies: [], origins: [] });
  },
});

export { expect };

/** Suffixe unique pour les données créées par un test (références, codes, noms). */
export function unique(prefixe: string): string {
  return `${prefixe}-${Date.now().toString(36).toUpperCase()}${Math.floor(Math.random() * 1000)}`;
}
