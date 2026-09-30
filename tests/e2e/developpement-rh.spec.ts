import { test, expect, unique } from './support/fixtures';
import { attendreModale, attendreToast, erreurDuChamp, erreurSous } from './support/ui';

/*
| H. Développement RH — CDC §4 6.1 (recrutement : suivi des candidats par étape,
| décision motivée), 6.2 (catalogue de formations, code unique par entreprise).
*/

test.describe('Catalogue de formations (Responsable RH)', () => {
  test.use({ profil: 'rh' });

  test('H1 — crée une formation puis refuse un second code identique', async ({ page }) => {
    const code = unique('FORM');
    await page.goto('/formation/formations');

    const creer = async (intitule: string) => {
      await page.getByRole('button', { name: 'Nouvelle formation' }).click();
      const modale = await attendreModale(page, 'Nouvelle formation');
      await modale.getByRole('textbox', { name: 'Code', exact: true }).fill(code);
      await modale.getByRole('textbox', { name: 'Intitulé', exact: true }).fill(intitule);
      await modale.getByRole('spinbutton', { name: 'Durée (h)' }).fill('14');
      await modale.getByRole('combobox', { name: 'Modalité' }).selectOption({ index: 1 });
      await modale.getByRole('button', { name: 'Enregistrer' }).click();
      return modale;
    };

    await creer('Sécurité incendie E2E');
    await attendreToast(page, /Formation|Enregistr/i);
    await expect(page.getByRole('row').filter({ hasText: code })).toBeVisible();

    const modale = await creer('Doublon E2E');
    await expect(erreurSous(modale.getByRole('textbox', { name: 'Code', exact: true })))
      .toHaveText('Ce code est déjà utilisé dans votre entreprise.');
  });
});

test.describe('Catalogue de formations (Direction générale)', () => {
  test.use({ profil: 'direction' });

  test('H2 — la Direction consulte le catalogue sans pouvoir le modifier', async ({ page }) => {
    const reponse = await page.goto('/formation/formations');
    expect(reponse?.status()).toBe(200);
    await expect(page.getByRole('button', { name: 'Nouvelle formation' })).toHaveCount(0);
  });
});

test.describe('Catalogue de formations (Auditeur)', () => {
  test.use({ profil: 'auditeur' });

  test('H2b — l\'Auditeur, limité aux rapports et à l\'audit, n\'y accède pas', async ({ page }) => {
    expect((await page.goto('/formation/formations'))?.status()).toBe(403);
  });
});

test.describe('Suivi des candidats (Responsable RH)', () => {
  test.use({ profil: 'rh' });

  test('H3 — fait avancer un candidat puis le refuse avec un motif obligatoire', async ({ page }) => {
    const nom = unique('CANDIDAT').toUpperCase();
    await page.goto('/recrutement/candidats/creer');
    await page.getByRole('combobox', { name: 'Source' }).selectOption({ index: 1 });
    await page.getByRole('textbox', { name: 'Nom', exact: true }).fill(nom);
    await page.getByRole('textbox', { name: 'Prénoms', exact: true }).fill('Afi');
    await page.getByRole('button', { name: 'Créer', exact: true }).click();
    await expect(page).toHaveURL(/\/recrutement\/candidats\/\d+$/);

    // Changement d'étape
    await page.getByRole('button', { name: 'Changer d\'étape' }).click();
    const etape = await attendreModale(page, 'Changer d\'étape');
    await etape.getByRole('combobox', { name: 'Nouvelle étape' }).selectOption({ index: 1 });
    await etape.getByRole('button', { name: 'Enregistrer' }).click();
    await attendreToast(page, /étape|Enregistr/i);

    // Refus : motif d'au moins 5 caractères (validation serveur)
    await page.getByRole('button', { name: 'Refuser' }).click();
    const refus = await attendreModale(page, 'Refuser le candidat');
    await refus.getByLabel('Motif de refus').fill('Non');
    await refus.getByRole('button', { name: 'Refuser' }).click();
    await expect(erreurDuChamp(refus, 'Motif de refus'))
      .toHaveText('Le motif doit contenir au moins 5 caractères.');

    await refus.getByLabel('Motif de refus').fill('Profil ne correspondant pas au poste.');
    await refus.getByRole('button', { name: 'Refuser' }).click();
    await attendreToast(page, 'Candidat refusé.');
    await expect(page.getByRole('button', { name: 'Retenir' })).toHaveCount(0);
  });
});
