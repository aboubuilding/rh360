# Tests E2E Playwright — EXPERT RH 360

Suite de bout en bout dérivée du cahier des charges v1.1 (groupes A à K).

## Prérequis (Laragon)

- PHP 8.3 de Laragon, MySQL 8.4 démarré (utilisateur `root`, sans mot de passe).
- Node 22 de Laragon dans le PATH :
  `$env:PATH = "C:\laragon\bin\nodejs\node-v22;$env:PATH"`
- Première fois : `npm install` puis `npx playwright install chromium`.
- Base dédiée à créer une fois :
  `& C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe -uroot -e "CREATE DATABASE IF NOT EXISTS expert_rh_360_e2e CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"`.
  Les
  commandes artisan de la suite refusent toute base sans le suffixe `_e2e` :
  la base de développement n'est jamais touchée.

## Lancer

| Commande | Effet |
| --- | --- |
| `npm run e2e` | Réinitialise la base (`migrate:fresh --seed` + `E2eSeeder`), ouvre les sessions, joue toute la suite |
| `$env:E2E_SANS_RESET='1'; npx playwright test tests/e2e/paie.spec.ts` | Rejoue une spec sans réinitialiser (itération rapide) |
| `npm run e2e:rapport` | Ouvre le rapport HTML (traces, vidéos, captures des échecs) |

Le serveur `php artisan serve` est démarré automatiquement sur le port **8001**
(1 worker : le serveur intégré de PHP est mono-thread sous Windows).

## Organisation

- `global-setup.ts` : réinitialisation de la base, puis connexion **une seule fois**
  par profil via le formulaire ; l'état (cookies de session) est stocké dans
  `.auth/<profil>.json` et réutilisé par toutes les specs.
- `support/fixtures.ts` : `test.use({ profil: 'drh' })` choisit la session d'un profil
  (défaut : super administrateur).
- `support/ui.ts` : toasts (`#toast-container`, rôles `status`/`alert`), confirmations
  SweetAlert2, modales Bootstrap, erreurs 422 affichées sous les champs.
- Comptes : `superadmin / Admin@2026!` ; `e2e_<profil> / E2e@2026!` pour admin, drh,
  rh, manager, direction, auditeur.

## Conventions

- Sélecteurs d'accessibilité : `getByRole` (avec `exact: true` pour les champs
  obligatoires, dont l'astérisque est masqué des lecteurs d'écran), `getByLabel`,
  `getByTestId` pour les widgets.
- Les données créées par un test portent un suffixe unique (`unique()`), les specs
  restent donc rejouables sans réinitialisation.
- Les circuits multi-acteurs (contrats, carrière, paie) ouvrent un contexte navigateur
  par profil et sont marqués `test.slow()`.
