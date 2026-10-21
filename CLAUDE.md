# CLAUDE.md — EXPERT RH 360

Guide de reprise du projet pour tout assistant de code (Claude, DeepSeek, Copilot…). **Lire en entier avant
toute modification.** Il décrit ce qui existe ; il ne remplace pas la lecture du code voisin. En cas de doute,
imiter le fichier le plus proche du même domaine.

---

## 1. Le projet en bref

ERP de ressources humaines pour des **entreprises privées au Togo** : dossiers du personnel, contrats,
carrière et avancements, intérims, congés et demandes d'absence, paie, santé et sécurité au travail (SST),
formation, performance, recrutement, pilotage.

- **Deux textes de référence** gouvernent les règles métier : le **Code du travail** (loi n°2021-012 du
  18 juin 2021) et la **Convention collective interprofessionnelle du Togo (CCIT) 2026** (en vigueur depuis
  le 20 mai 2026). Les règles en citent les articles **dans les commentaires du code**, jamais à l'écran.
- **Multi-entreprises** : chaque donnée métier porte `entreprise_id` et est cloisonnée automatiquement.
- **Langue** : tout est en **français** — code (classes, méthodes, variables, tables, colonnes), interface,
  messages de validation, commentaires, tests, messages de commit.
- **Cahier des charges** : `Cahier_des_charges_EXPERT_RH_360_Laravel_v1.1.docx` à la racine.

---

## 2. Stack technique

| Élément | Version et usage |
|---|---|
| PHP | 8.2+ (dév. : PHP 8.2 et 8.3 de Laragon, Windows) |
| Laravel | 12 (`bootstrap/app.php` au nouveau format, pas de Kernel) |
| Base de données | **MySQL 8.4** (Laragon, `root` sans mot de passe). Base de dév. : `expert_rh_360` ; tests : `expert_rh_360_test` ; E2E : `expert_rh_360_e2e` |
| Front | **Blade + Bootstrap 5** (thème « Kumbh Sans / Playfair », couleurs `--rh-*`), **jQuery** (AJAX), **SweetAlert2** (confirmations), Font Awesome. Vite et Tailwind sont installés mais l'interface métier utilise Bootstrap et les assets de `public/app/assets` |
| PDF | `barryvdh/laravel-dompdf` (bulletins, actes, attestations) |
| Excel | `maatwebsite/excel` (imports du personnel, planning des congés) |
| Tests | **Pest 3** (`tests/Feature`, `tests/Unit`) ; **Playwright** pour l'E2E (`tests/e2e`) |
| Qualité | Larastan, Pint (voir § 9 : ne pas reformater les fichiers existants) |
| ZIP | extension PHP `zip` (export des bulletins en archive) |

---

## 3. Commandes courantes

Toutes depuis la racine `C:\laragon\www\rh360` (Git Bash ou PowerShell).

```bash
# Installation
composer install
npm install
php artisan key:generate
php artisan migrate --seed                 # base de dév. + données de démonstration

# Serveur de développement
php artisan serve --host=127.0.0.1 --port=8000
# Compte : superadmin / Admin@2026!   (http://127.0.0.1:8000/connexion)

# Migrations
php artisan migrate                        # toujours additif, jamais de migrate:fresh sur la base de dév.
php artisan migrate:status

# Données de démonstration supplémentaires (à la demande, hors DatabaseSeeder)
php artisan db:seed --class=PaieDemoSeeder # 11 salariés DEMO-PAIE-01 à 11, un cas de paie chacun

# Tests (Pest) — base MySQL expert_rh_360_test, forcée par phpunit.xml
php vendor/bin/pest                                  # suite complète (~700 tests, 10 à 15 min)
php vendor/bin/pest tests/Feature/Paie               # un dossier
php vendor/bin/pest tests/Feature/Paie/SortiePaieTest.php --filter="licenciement"

# Tests E2E (Playwright) — base expert_rh_360_e2e, port 8001
npm run e2e
npm run e2e:rapport

# Commandes métier (planifiées dans routes/console.php)
php artisan rh:calculer-paie-mensuelle
php artisan conges:acquerir --entreprise=1           # acquisition mensuelle des congés payés
php artisan conges:cloturer-lots --entreprise=1
php artisan demandes:echeances                       # requalification des justificatifs en retard
php artisan demandes:installer-catalogue [--aligner] # types de demande et événements de permission
php artisan classification:installer-bareme-2026     # barème CCIT 2026 (Annexes I et II)
php artisan interims:balayer | interims:appliquer-reclassements | interims:reprise [--dry-run]
php artisan avancements:distinctions
```

**Règles impératives sur les tests**

- **Ne jamais lancer deux exécutions de tests en même temps** : elles partagent la base `expert_rh_360_test`
  (`RefreshDatabase` la recrée), ce qui fait échouer les deux (« Table migrations doesn't exist »).
- Chaque test appelle `$this->seed()` : compter environ 50 à 60 s pour le premier test d'un fichier.
  Pour une itération rapide, filtrer (`--filter`) plutôt que lancer toute la suite.
- La suite complète doit être **verte avant chaque commit**.

---

## 4. Architecture

Architecture **orientée domaine** (inspirée de la Clean Architecture et du DDD), sans couche Repository :
les modèles Eloquent sont utilisés directement, la logique métier vit dans des Actions et des Services.

```
app/
├── Domain/                      ← tout le métier, un dossier par domaine
│   ├── Administration/          entreprises, utilisateurs, permissions, journal d'audit
│   ├── Organisation/            types de structures, structures, postes
│   ├── Classification/          référentiels, classes, catégories, échelons, positions (barème)
│   ├── Personnel/               salariés, foyer (MembreFoyer), documents, affectations, imports
│   ├── Contrats/                contrats, avenants, essai, alertes, pièces
│   ├── Carriere/                situations, mouvements de carrière, avancements, ancienneté utile, distinctions
│   ├── Interim/                 intérim interne, agences d'intérim, prêt de main-d'œuvre
│   ├── Conges/                  types de demande, demandes, absences, grand livre des congés, maternité, jours fériés
│   ├── Paie/                    rubriques, modèles, périodes, moteur, bulletins, retenues, sorties
│   ├── Sst/                     risques, visites médicales, EPI, habilitations, événements
│   ├── Formation/  Performance/  Recrutement/  DeveloppementRh/  Pilotage/
│   └── Shared/                  Enums/Etat, Scopes, Traits (AvecEtat, BelongsToEntreprise), Services/ParametresEntreprise
├── Http/
│   ├── Controllers/{Domaine}/   contrôleurs fins, un dossier par domaine
│   └── Middleware/VerifierPermission.php   (alias « permission »)
├── Console/Commands/            commandes artisan métier
└── Providers/                   AppServiceProvider, DomainServiceProvider (policies, singletons), EventServiceProvider
```

Chaque domaine suit la même structure :

| Dossier | Rôle | Exemple |
|---|---|---|
| `Models/` | Modèles Eloquent : `$table` explicite, `$fillable`, `casts()`, relations, scopes, petits helpers | `Conges/Models/DemandeConge.php` |
| `Enums/` | Enums PHP adossés à une chaîne : `libelle()`, `couleur()`, `options()` ; pour un statut, `transitionsAutorisees()` et `peutTransitionnerVers()` | `Conges/Enums/StatutDemandeConge.php` |
| `Actions/` | **Un cas d'usage par classe**, méthode `executer(...)`, en transaction | `Conges/Actions/SoumettreDemandeConge.php` |
| `Services/` | Calculs et règles réutilisables (calculateurs, circuits, préparateurs, installateurs, paramètres) | `Paie/Services/MoteurPaie.php` |
| `Requests/` | FormRequest : `authorize()` via `$this->user()->peut('...')`, `rules()`, **messages en français**, `prepareForValidation()` si besoin | `Paie/Requests/StoreRubriquePaieRequest.php` |
| `Policies/` | Autorisations par modèle, enregistrées dans `DomainServiceProvider` | `Conges/Policies/DemandeCongePolicy.php` |
| `Events/` `Listeners/` | Effets de bord (notifications, resynchronisations) | `Conges/Events/DemandeAutorisee.php` |

### Flux d'une requête

`Route (routes/web/{domaine}.php)` → `Controller` (`$this->authorize(...)`, FormRequest) → `Action::executer()`
ou `Service` → modèles → réponse **JSON si `$request->expectsJson()`**, sinon `redirect()->with('success'|'error', ...)`.

---

## 5. Conventions de codage

### 5.1 Nommage

- **Français partout**, sans accents dans les identifiants : `calculerPrime()`, `$dateCessation`,
  `SoldeConge`, table `soldes_conges`, colonne `date_debut_fixee`.
- Classes en PascalCase avec un nom métier : `CalculateurSoldeToutCompte`, `GestionJustificatifs`,
  `PreparateurPaieAbsences`, `InstallateurCatalogueDemandes`, `ResolveurModelePaie`.
- Suffixes établis : `Calculateur*` (calcul pur), `Circuit*` (transitions de statut), `Preparateur*`
  (alimente les saisies de paie d'une période), `Installateur*` (catalogue idempotent), `Parametres*`
  (paramètres par entreprise), `Controle*` (contrôles bloquants), `Gestionnaire*`.
- Actions à l'infinitif : `CreerDemandeConge`, `AutoriserDemandeConge`, `ReporterDepartConge`.
- Tables au pluriel en snake_case (`demandes_conges`, `mouvements_conge`), clés étrangères `xxx_id`.
- Méthodes de modèle booléennes : `estDeDroit()`, `estFigee()`, `aUnPlafond()`, `peutEtreCalculee()`.
- Valeurs d'enum stockées : les statuts des circuits sont stockés en anglais (`draft`, `submitted`,
  `authorized`, `awaiting_document`, `open`, `validated`…), les cases PHP sont en français
  (`StatutDemandeConge::ATTENTE_JUSTIFICATIF`). Les autres enums stockent souvent du français
  (`conge_paye`, `cotisation_syndicale`). Toujours vérifier l'enum avant d'écrire une valeur, et
  **ne jamais renommer une valeur stockée**.

### 5.2 Multi-entreprises et état

- Tout modèle métier utilise `BelongsToEntreprise` : scope global sur `entreprise_id` de l'utilisateur connecté
  et remplissage automatique à la création. En console ou sans utilisateur, filtrer explicitement avec
  `Modele::pourEntreprise($entrepriseId)` (qui retire le scope).
- Les modèles « référentiels » utilisent `AvecEtat` : colonne `etat` (enum `Etat` : `1` actif, `0` inactif,
  `-1` supprimé), scope global qui masque les supprimés, `marquerSupprime()` au lieu de supprimer.
- Toujours passer `entreprise_id` et `'etat' => 1` lors des créations dans les seeders et les services.

### 5.3 Autorisations

- Permissions nommées `domaine.action` (`paie.view`, `paie.manage`, `paie.calculer`, `conges.validate`…),
  catalogue dans `database/seeders/PermissionSeeder.php`, rôles dans `PermissionRoleSeeder`.
- Dans un contrôleur : `$this->authorize('permission', 'paie.manage')` (Gate générique) ou
  `$this->authorize('autoriser', $demande)` (policy).
- En Blade : `@can('permission', 'paie.manage') … @endcan`.
- Rôles : `super_admin`, `admin`, `drh`, `rh`, `manager`, `direction`, `auditeur`.

### 5.4 Règles métier et traçabilité

- **Un acte effectif ne se modifie pas : on le corrige par un nouvel acte.** Plusieurs modèles sont en
  insertion seule et lèvent une `LogicException` à la modification ou à la suppression :
  `HistoriqueDemande`, `MouvementConge` (grand livre des congés). Les mouvements de carrière et les contrats
  ont leur propre historique.
- Les transitions de statut passent par un **circuit** (`CircuitDemandeConge`, `CircuitContrat`…) qui
  vérifie `peutTransitionnerVers()`, enregistre l'historique et appelle `ServiceAudit::tracer()`.
- Une règle violée lève une **`\DomainException` avec un message en français destiné à l'utilisateur**.
  Le contrôleur la transforme en `422 {message}` (JSON) ou en `back()->with('error', ...)`.
- Les messages ne citent **jamais** d'article de loi ou de convention à l'écran (exigence explicite) ; la
  référence va dans le commentaire PHP au-dessus de la règle (`// CCIT art. 50 : ...`).
- Les montants sont en **FCFA**, arrondis à l'entier à l'affichage (`number_format($m, 0, ',', ' ')`).

### 5.5 Paramétrage

- **Paramètres par entreprise** (points d'interprétation des textes) : classe abstraite
  `Shared/Services/ParametresEntreprise`, étendue par `ParametresInterim` (`config/interim.php`),
  `ParametresAvancement` (`config/avancement.php`), `ParametresDemandes` (`config/demandes.php`).
  - Valeur par défaut = fichier `config/*.php` ; surcharges stockées dans `parametres_*` (JSON) ;
    catalogue des définitions dans `Catalogue Parametres*` (`groupe`, `libelle`, `type`, `min`, `max`,
    `options`, `reference` non affichée).
  - Enregistrés en `scoped` dans `DomainServiceProvider`. En console : `->pourEntreprise($id)`. Dans un test
    qui modifie un paramètre : `app()->forgetScopedInstances()`.
  - Écran générique : `resources/views/partials/_formulaire-parametres.blade.php`.
  - Les bornes `min` et `max` empêchent toute valeur moins favorable que les textes.
- **Paramètres versionnés par date d'effet** (valeurs externes ou réglementaires) : `regles_cotisation`,
  `regles_irpp`, `regles_anciennete`, `parametres_legaux_paie` (SMIG, barème de quotité en
  `valeur_json`). On lit toujours la version en vigueur à la date du calcul.
- Ne **jamais** coder en dur un taux, un plafond ou un montant susceptible d'évoluer.

### 5.6 Interface (Blade)

- Gabarit : `@extends('layouts.app')`, sections `title`, `page_title`, `page_icon`, `breadcrumb`,
  `page_actions`, `contenu`, scripts dans `@push('js')`.
- Champs de formulaire : composant **`<x-field name="..." label="..." type="text|select|date|number|textarea|checkbox|file" :options="[...]" required help="..." />`**.
  Une case à cocher émet un champ caché `0` puis la case `1`.
- Formulaires AJAX : jQuery `$.ajax({ data: $form.serialize(), headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })`,
  erreurs 422 affichées sous les champs (`is-invalid` et `invalid-feedback`), puis `window.showToast()`
  ou `window.showToastThenReload()`.
- Modales Bootstrap pour les actions courtes ; confirmations SweetAlert2 (`Swal.fire`) ou `onsubmit="return confirm(...)"`.
- **Aucun tableau ne doit défiler horizontalement** : `mystyle.css` fait revenir les cellules à la ligne et un
  script du layout empile les lignes en fiches sous 768 px (attribut `data-label`). Garder des tableaux
  `<table class="table">` simples avec un `<thead>` et peu de colonnes.
- Menu : `resources/views/layouts/partials/_header.blade.php` (tableau `[libellé, route, [permissions], icône]`).
- Messages flash affichés : `success` et `error` seulement (pas de `warning`).

### 5.7 Routes

- Un fichier par domaine dans `routes/web/` (déclaré dans `bootstrap/app.php`), groupe
  `middleware('auth')->prefix('domaine')->name('domaine.')`.
- **Routes fixes avant les routes à paramètre** (`demandes/creer` avant `demandes/{demande}`).
- Noms de route en français : `conges.demandes.soumettre`, `paie.generation.telecharger`.

### 5.8 Migrations

- Nommage daté : `AAAA_MM_JJ_NNNNNN_verbe_objet.php` (`2026_10_21_000014_creer_sorties_paie.php`), avec un
  docblock en français qui explique le pourquoi.
- **Toujours additives** : nouvelles colonnes nullable ou avec défaut, nouvelles tables. Ne jamais modifier une
  migration déjà exécutée ; ne jamais supprimer de données métier.
- Les rubriques et les catalogues de référence sont créés par des **installateurs idempotents** appelés à la
  fois par une migration de données (entreprises existantes) et par le seeder (nouvelles installations).

### 5.9 Tests (Pest)

- Un fichier par sujet, docblock en tête citant les règles testées, puis :
  ```php
  beforeEach(function () {
      $this->travelTo(Carbon::parse('2026-10-01 09:00'));   // dates déterministes
      $this->seed();
      $this->actingAs(Utilisateur::where('role', Utilisateur::ROLE_SUPER_ADMIN)->first());
      // isoler les données : désactiver les autres salariés, vider demandes/absences du salarié testé…
  });
  it('décrit le comportement attendu en français', function () { ... });
  ```
- Tester par les routes (`$this->post(route(...))`, `postJson`) et par les services. Montants vérifiés
  exactement (`toBe(116666.67)`).
- Les données de démonstration du seeder peuvent chevaucher les dates de test : nettoyer (`DB::table('demandes_conges')->where('salarie_id', …)->delete()`).

---

## 6. Points clés par module

### Paie (`app/Domain/Paie`)
- **`MoteurPaie`** : `calculer(PeriodePaie)` lance les préparateurs (intérims, règles connexes,
  `PreparateurPaieAbsences`, `PreparateurPaieSorties`), puis pour chaque salarié `construire()` (calcul pur,
  aucune écriture, réutilisé par l'aperçu) et `calculerBulletin()` (enregistrement, anomalies, journal des
  recalculs).
- Ordre de calcul : présence du mois (entrée, cessation) → minima (catégorie, SMIG) → modèle (manuel daté,
  sinon modèle de la catégorie) → salaire de base → lignes du modèle et éléments individuels (proratisés) →
  heures supplémentaires et rappels **du salarié** → saisies de la période → prime d'ancienneté → brut
  (**hors remboursements de frais**, `rubriques_paie.est_frais`) → cotisations sur leur assiette (parts
  patronales affichées, jamais déduites) → impôt → retenues encadrées → net.
- Rubriques calculées par le moteur (non remplaçables) : `SAL-BASE`, `PRIME-ANC`, `HS`, `RAPPEL`, `CNSS`, `AMU`, `IRPP`.
- **Retenues** : seuls les types de `TypeRetenue` sont admis ; cotisation syndicale avec `accord_le` ;
  prêts (`PretSalarie`) après `verifie_le` ; saisies (`SaisieAttribution`) ; plafond par la quotité
  (`CalculateurQuotite`). Une retenue non autorisée produit une **anomalie bloquante**.
- **Anomalies** (`anomalies_paie`) : `bloquante` empêche `ValiderPeriode`, `alerte` informe.
- Validation : date de paiement au plus 8 jours après la fin du mois, ni dimanche ni jour férié.
- Saisies générées : colonne `saisies_paie.source` (`interim`, `absences`, `sortie`, `distinction`…),
  recalculées à chaque calcul ; une saisie manuelle n'est jamais écrasée (une saisie par rubrique, salarié
  et période : contrainte unique).
- Écrans : Générer la paie (`paie.generation.*`, PDF et ZIP), aperçu des modèles, éléments des salariés,
  sorties (solde de tout compte), paramètres légaux.

### Congés et demandes (`app/Domain/Conges`)
- `TypeConge` porte les règles (famille, de droit ou soumis à accord, délais, plafond, ancienneté minimale).
  Catalogue installé par `InstallateurCatalogueDemandes`.
- Circuit : `CreerDemandeConge` → `SoumettreDemandeConge` (contrôles `ControleDemande`, échéance du
  justificatif) → `GestionJustificatifs::orienter()` (validation automatique des absences de droit) →
  requalification à l'échéance. Une absence de droit ne se refuse pas.
- **Grand livre des congés payés** (`GrandLivreConges`, table `mouvements_conge`) : lots annuels,
  acquisition mensuelle, prise du lot le plus ancien, report limité, reprise automatique des anciens
  soldes. `soldes_conges` n'est plus que la **projection** du grand livre pour le congé payé.
- Jours fériés et jours ouvrés : `CalendrierJoursFeries`.

### Carrière, intérims, contrats
- Ancienneté utile : `Carriere/Services/CalculateurAncienneteUtile` (périodes exclues, emplois antérieurs
  cumulés). Prime d'ancienneté : `Paie/Services/CalculateurAnciennete` + `RegleAnciennete` versionnée.
- Intérim : `Interim/Services/InterimInterneService`, indemnité à partir du 3e mois, contrôles de clôture
  de paie (`ControleClotureInterims`).
- Barème CCIT 2026 : `Classification/Services/InstallateurBaremeCcit2026` (code `CCIT-2026`).

---

## 7. Données de démonstration et comptes

- `php artisan migrate --seed` : entreprise 1, permissions, organisation, classification, salariés,
  contrats, carrière, congés, paie, SST, formation, performance, recrutement.
- `PaieDemoSeeder` (à la demande) : 11 cas de paie commentés (le cas 11 est volontairement bloquant).
- `E2eSeeder` : comptes `e2e_<profil>` / `E2e@2026!` pour les tests Playwright.
- Super administrateur : **`superadmin` / `Admin@2026!`**.

---

## 8. Avant de livrer une modification

1. Lire les fichiers voisins du même domaine et reproduire leur style (densité des commentaires, nommage,
   structure de la classe).
2. Écrire ou compléter un test Pest dans `tests/Feature/{Domaine}/`.
3. Vérifier la syntaxe : `php -l chemin/fichier.php`.
4. Lancer les tests du dossier concerné, puis **la suite complète** (une seule exécution à la fois).
5. `php artisan migrate` sur la base de dév. si une migration a été ajoutée.
6. Commit en français, format Conventional Commits : `feat(paie): …`, `fix(conges): …`, `test(...)`,
   avec un corps qui liste les règles ajoutées.

---

## 9. Pièges connus (à lire absolument)

- **Apostrophes dans les chaînes PHP** : le code est en français ; dans une chaîne entre apostrophes,
  échapper chaque apostrophe (`'L\'échéance'`) ou utiliser des guillemets doubles. Une apostrophe non
  échappée casse tout le fichier (`syntax error, unexpected identifier`). Toujours relancer `php -l`.
- **Ne pas lancer `pint` sur des fichiers existants** : il reformate des fichiers entiers et produit des
  diffs illisibles. Formater seulement les fichiers que vous créez, ou pas du tout.
- **Dates Carbon** : `$a->max($b)` et `$a->min($b)` peuvent renvoyer l'instance d'origine ; faire `->copy()`
  avant de la modifier.
- **Enums castés** : comparer avec l'enum (`$x->statut === StatutEmploi::ACTIF`), pas avec une chaîne.
- **Scopes globaux** : dans une commande, un job ou un seeder, il n'y a pas d'utilisateur connecté :
  filtrer avec `pourEntreprise($id)` ou `withoutGlobalScopes()`.
- **Paramètres `scoped`** : un test qui change un paramètre doit appeler `app()->forgetScopedInstances()`.
- **`assertSee`** échappe les caractères HTML (apostrophes) : utiliser `assertSee('…', false)` si besoin, et
  garder le texte testé sur une seule ligne dans la vue (les retours à la ligne Blade cassent la recherche).
- **Contrainte unique `saisies_paie (periode, salarie, rubrique)`** : cumuler les montants d'une même
  rubrique avant d'insérer.
- **Fins de ligne** : le dépôt est sous Windows (CRLF et LF mélangés) ; ne pas convertir des fichiers entiers.
- **Statuts historiques en anglais** : ne pas « traduire » les valeurs stockées (`draft`, `submitted`, `open`…).
- **Base de tests MySQL partagée** : `tests/Pest.php` mentionne SQLite en mémoire, mais `phpunit.xml` force
  MySQL `expert_rh_360_test` : c'est cette base qui sert.

---

## 10. À ne jamais faire

- Supprimer ou réécrire une migration déjà exécutée, ou lancer `migrate:fresh` sur `expert_rh_360`.
- Afficher une référence d'article de loi dans l'interface.
- Coder en dur un taux, un plafond, un barème ou le SMIG.
- Modifier ou supprimer un acte effectif (bulletin validé, mouvement de grand livre, historique) au lieu de
  le corriger par un nouvel acte.
- Ajouter une retenue sur salaire hors des cas de `TypeRetenue`, ou déduire une cotisation patronale du net.
- Désactiver un contrôle bloquant pour faire passer un test : corriger les données du test.
- Introduire une nouvelle dépendance, un framework front ou une couche Repository sans nécessité.
