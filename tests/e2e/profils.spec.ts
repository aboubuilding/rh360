import { test, expect } from './support/fixtures';
import type { Profil } from './support/comptes';

/*
| K. Profils et permissions — CDC §3, §5 et tableaux « Profils concernés » du §4.
| Menus du §2 visibles selon le profil, aucune entrée de menu menant à une erreur,
| et refus (403) en accès direct à un écran hors périmètre.
*/

const MENUS = [
  'Tableau de bord', 'Dossiers RH', 'Carrière & Mobilité', 'Congés & Absences',
  'Paie', 'Développement RH', 'Santé & Sécurité', 'Administration',
] as const;
type Menu = (typeof MENUS)[number];

const MENUS_ATTENDUS: Record<Profil, Menu[]> = {
  superadmin: [...MENUS],
  drh: [...MENUS],
  rh: [...MENUS],
  admin: ['Tableau de bord', 'Administration'],
  manager: ['Tableau de bord', 'Dossiers RH', 'Congés & Absences', 'Développement RH', 'Administration'],
  direction: ['Tableau de bord', 'Dossiers RH', 'Carrière & Mobilité', 'Congés & Absences', 'Paie',
    'Développement RH', 'Santé & Sécurité', 'Administration'],
  auditeur: ['Tableau de bord', 'Dossiers RH', 'Paie', 'Santé & Sécurité', 'Administration'],
};

/** Écrans hors périmètre, refusés en accès direct (§4 « Profils concernés », §5). */
const INTERDITS: Partial<Record<Profil, string[]>> = {
  admin: ['/salaries', '/contrats', '/paie/periodes', '/sst/visites', '/admin/permissions/matrice'],
  rh: ['/admin/utilisateurs', '/admin/audit', '/admin/permissions/matrice'],
  manager: ['/paie/periodes', '/contrats', '/sst/visites', '/carriere/mouvements'],
  direction: ['/sst/visites', '/sst/evenements', '/sst/risques', '/sst/epi', '/sst/habilitations', '/admin/audit'],
  auditeur: ['/salaries', '/carriere/mouvements', '/conges', '/sst/visites', '/formation/formations'],
};

for (const [profil, attendus] of Object.entries(MENUS_ATTENDUS) as [Profil, Menu[]][]) {
  test.describe(`Profil ${profil}`, () => {
    test.use({ profil });

    test(`K — affiche uniquement les menus du profil ${profil}`, async ({ page }) => {
      await page.goto('/tableau-de-bord');
      const navigation = page.getByRole('navigation', { name: 'Menu principal' });

      for (const menu of MENUS) {
        const bouton = navigation.getByRole('button', { name: menu, exact: true });
        if (attendus.includes(menu)) {
          await expect(bouton, `menu « ${menu} » attendu`).toBeVisible();
        } else {
          await expect(bouton, `menu « ${menu} » interdit`).toHaveCount(0);
        }
      }
    });

    test(`K — chaque entrée de menu du profil ${profil} s'ouvre sans erreur`, async ({ page }) => {
      test.slow();
      await page.goto('/tableau-de-bord');
      const liens = await page.getByRole('navigation', { name: 'Menu principal' })
        // Sous-menus fermés (masqués) : on inclut les entrées cachées
        .getByRole('menuitem', { includeHidden: true })
        .evaluateAll((els) => els.map((e) => [e.textContent?.trim() ?? '', (e as HTMLAnchorElement).getAttribute('href') ?? '']));

      expect(liens.length).toBeGreaterThan(0);
      const erreurs: string[] = [];
      for (const [libelle, href] of liens) {
        const reponse = await page.goto(href);
        const statut = reponse?.status() ?? 0;
        if (statut >= 400) erreurs.push(`${statut} ${libelle} (${href})`);
      }
      expect(erreurs, 'entrées de menu en erreur').toEqual([]);
    });

    const interdits = INTERDITS[profil] ?? [];
    if (interdits.length) {
      test(`K — refuse l'accès direct aux écrans hors périmètre (${profil})`, async ({ page }) => {
        for (const url of interdits) {
          const reponse = await page.goto(url);
          expect(reponse?.status(), `${profil} → ${url}`).toBe(403);
        }
      });
    }
  });
}

test.describe('Menu utilisateur', () => {
  test.use({ profil: 'drh' });

  test('K — le menu du compte affiche le nom et le profil de l\'utilisateur', async ({ page }) => {
    await page.goto('/tableau-de-bord');
    await page.getByRole('button', { name: /Mon compte : DRH E2E/ }).click();

    const menu = page.locator('#hbtp-menu-compte');
    await expect(menu).toBeVisible();
    await expect(menu).toContainText('DRH E2E');
    await expect(menu.getByRole('menuitem', { name: 'Se déconnecter' })).toBeVisible();
  });

  test('K — ouvre un sous-menu au clic et le referme avec Échap', async ({ page }) => {
    await page.goto('/tableau-de-bord');
    const bouton = page.getByRole('navigation', { name: 'Menu principal' }).getByRole('button', { name: 'Paie', exact: true });

    await bouton.click();
    await expect(bouton).toHaveAttribute('aria-expanded', 'true');
    await expect(page.getByRole('menuitem', { name: 'Périodes & calcul' })).toBeVisible();

    await page.keyboard.press('Escape');
    await expect(bouton).toHaveAttribute('aria-expanded', 'false');
  });
});
