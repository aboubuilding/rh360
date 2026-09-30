import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

/** Profils du cahier des charges (§3) et comptes créés par Database\Seeders\E2eSeeder. */
export type Profil = 'superadmin' | 'admin' | 'drh' | 'rh' | 'manager' | 'direction' | 'auditeur';

export const MOT_DE_PASSE_E2E = 'E2e@2026!';

export const COMPTES: Record<Profil, { identifiant: string; motDePasse: string; libelle: string }> = {
  superadmin: { identifiant: 'superadmin', motDePasse: 'Admin@2026!', libelle: 'Super administrateur' },
  admin: { identifiant: 'e2e_admin', motDePasse: MOT_DE_PASSE_E2E, libelle: 'Administrateur' },
  drh: { identifiant: 'e2e_drh', motDePasse: MOT_DE_PASSE_E2E, libelle: 'DRH' },
  rh: { identifiant: 'e2e_rh', motDePasse: MOT_DE_PASSE_E2E, libelle: 'Responsable RH' },
  manager: { identifiant: 'e2e_manager', motDePasse: MOT_DE_PASSE_E2E, libelle: 'Manager' },
  direction: { identifiant: 'e2e_direction', motDePasse: MOT_DE_PASSE_E2E, libelle: 'Direction générale' },
  auditeur: { identifiant: 'e2e_auditeur', motDePasse: MOT_DE_PASSE_E2E, libelle: 'Auditeur' },
};

export const PROFILS = Object.keys(COMPTES) as Profil[];

/** Fichier de session (cookies) enregistré par global-setup pour un profil. */
export function sessionDe(profil: Profil): string {
  return path.join(__dirname, '..', '.auth', `${profil}.json`);
}
