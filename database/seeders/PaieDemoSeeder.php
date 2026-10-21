<?php

namespace Database\Seeders;

use App\Domain\Carriere\Models\SituationCarriere;
use App\Domain\Classification\Models\PositionClassification;
use App\Domain\Classification\Models\ReferentielClassification;
use App\Domain\Classification\Services\InstallateurBaremeCcit2026;
use App\Domain\Conges\Enums\QualificationAbsence;
use App\Domain\Conges\Enums\StatutDemandeConge;
use App\Domain\Conges\Models\Absence;
use App\Domain\Conges\Models\DemandeConge;
use App\Domain\Conges\Models\TypeConge;
use App\Domain\Conges\Services\GrandLivreConges;
use App\Domain\Conges\Services\InstallateurCatalogueDemandes;
use App\Domain\Organisation\Models\Poste;
use App\Domain\Organisation\Models\Structure;
use App\Domain\Paie\Models\ElementPaieSalarie;
use App\Domain\Paie\Models\HeureSupplementaire;
use App\Domain\Paie\Models\ModelePaie;
use App\Domain\Paie\Models\ModelePaieRubrique;
use App\Domain\Paie\Models\ParametreLegalPaie;
use App\Domain\Paie\Models\PeriodePaie;
use App\Domain\Paie\Models\PretSalarie;
use App\Domain\Paie\Models\RubriquePaie;
use App\Domain\Paie\Models\SaisieAttribution;
use App\Domain\Paie\Models\SortieSalarie;
use App\Domain\Paie\Services\CalculateurSoldeToutCompte;
use App\Domain\Paie\Services\InstallateurRubriquesRetenues;
use App\Domain\Paie\Services\MoteurPaie;
use App\Domain\Paie\Services\PreparateurPaieAbsences;
use App\Domain\Personnel\Enums\StatutDossier;
use App\Domain\Personnel\Enums\StatutEmploi;
use App\Domain\Personnel\Models\Affectation;
use App\Domain\Personnel\Models\Salarie;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Données de démonstration de la paie, à lancer à la demande :
 *   php artisan db:seed --class=PaieDemoSeeder
 *
 * Onze salariés « DEMO-PAIE-xx », chacun illustrant un cas traité par la paie du mois en cours,
 * puis le calcul de la période. Relancer le seeder remet ces salariés dans leur état de départ.
 * Le barème de quotité saisissable est une valeur d'EXEMPLE, à remplacer par celle du décret.
 */
class PaieDemoSeeder extends Seeder
{
    private const ENTREPRISE = 1;

    private Carbon $debut;

    private PeriodePaie $periode;

    public function run(): void
    {
        $this->debut = today()->startOfMonth();
        $this->installerReferentiels();

        $this->periode = PeriodePaie::withoutGlobalScopes()->firstOrCreate(
            ['entreprise_id' => self::ENTREPRISE, 'annee' => $this->debut->year, 'mois' => $this->debut->month],
            ['statut' => 'open', 'etat' => 1],
        );
        if ($this->periode->estFigee()) {
            $this->command?->warn("La période {$this->periode->libelle} est validée : les données de démonstration n'y sont pas ajoutées.");

            return;
        }

        $cas = [
            ['01', 'Agent standard', 'AKUE', 'Kossi', 'M', 'E4-B', 75500, '2020-01-01'],
            ['02', 'Entrée en cours de mois', 'MENSAH', 'Afi', 'F', 'E4-A', 71500, $this->debut->copy()->addDays(9)->toDateString()],
            ['03', 'Heures supplémentaires', 'KODJO', 'Yao', 'M', 'E4-B', 75500, '2018-03-01'],
            ['04', 'Congé payé dans le mois', 'ADJOVI', 'Ama', 'F', 'M1-A', 103500, '2017-06-01'],
            ['05', 'Arrêt maladie', 'TCHALLA', 'Essowè', 'M', 'E3-A', 64500, '2023-02-01'],
            ['06', 'Congé de maternité', 'AGBEKO', 'Enyonam', 'F', 'E5-A', 80500, '2021-09-01'],
            ['07', 'Absence non justifiée', 'GNASSINGBE', 'Komla', 'M', 'E2-A', 57500, '2022-04-01'],
            ['08', 'Prêt et saisie sur salaire', 'DOGBE', 'Sena', 'F', 'C1-A', 161500, '2015-01-01'],
            ['09', 'Cotisation syndicale', 'AMEGAN', 'Kodzo', 'M', 'E6-A', 89500, '2016-11-01'],
            ['10', 'Sortie : licenciement, préavis dispensé', 'ATTISSO', 'Mawuli', 'M', 'M2-C', 125000, '2019-05-15'],
            ['11', 'Anomalie volontaire : salaire sous le SMIG', 'BAWA', 'Abla', 'F', 'E1-U', 50000, '2024-01-01'],
        ];

        $salaries = [];
        foreach ($cas as [$code, $libelle, $nom, $prenoms, $sexe, $position, $salaire, $embauche]) {
            $salaries[$code] = $this->salarie($code, $libelle, $nom, $prenoms, $sexe, $position, $salaire, $embauche);
        }

        $this->scenarios($salaries);

        $resultats = app(MoteurPaie::class)->calculer($this->periode->fresh());
        $this->command?->info(sprintf(
            'Paie de démonstration de %s : %d bulletin(s) calculé(s), %d anomalie(s) bloquante(s) (dont le cas 11, volontaire).',
            $this->periode->libelle, $resultats['bulletins_calcules'], $resultats['anomalies_bloquantes'],
        ));
    }

    private function installerReferentiels(): void
    {
        app(InstallateurBaremeCcit2026::class)->installer(self::ENTREPRISE);
        app(InstallateurCatalogueDemandes::class)->installer(self::ENTREPRISE);
        PreparateurPaieAbsences::installerRubriques(self::ENTREPRISE);
        InstallateurRubriquesRetenues::installer(self::ENTREPRISE);
        CalculateurSoldeToutCompte::installerRubriques(self::ENTREPRISE);

        // Barème de quotité saisissable : valeurs d'EXEMPLE, à remplacer par le décret en vigueur.
        if (! ParametreLegalPaie::withoutGlobalScopes()->where('entreprise_id', self::ENTREPRISE)->where('cle', 'quotite_saisissable')->exists()) {
            ParametreLegalPaie::create([
                'entreprise_id' => self::ENTREPRISE, 'cle' => 'quotite_saisissable', 'debut_effet' => '2026-01-01',
                'valeur_json' => [[30000, 0.1], [75000, 0.2], [null, 0.3333]],
                'commentaire' => 'EXEMPLE de démonstration — à remplacer par le barème du décret',
            ]);
        }

        // Un modèle de bulletin par catégorie du barème, avec transport et logement en montant individuel.
        $referentiel = ReferentielClassification::withoutGlobalScopes()->where('entreprise_id', self::ENTREPRISE)
            ->where('code', InstallateurBaremeCcit2026::CODE_REFERENTIEL)->sole();
        $rubriques = RubriquePaie::withoutGlobalScopes()->where('entreprise_id', self::ENTREPRISE)->pluck('id', 'code');
        foreach ($referentiel->categories()->get() as $categorie) {
            $modele = ModelePaie::withoutGlobalScopes()->firstOrCreate(
                ['entreprise_id' => self::ENTREPRISE, 'categorie_id' => $categorie->id],
                ['nom' => 'Modèle '.$categorie->code.' — '.$categorie->libelle, 'actif' => true, 'etat' => 1],
            );
            foreach (['SAL-BASE' => 10, 'PRIME-ANC' => 15, 'PRIME-TRANSP' => 20, 'PRIME-LOGT' => 30, 'CNSS' => 60, 'AMU' => 70, 'IRPP' => 90] as $code => $ordre) {
                if (isset($rubriques[$code])) {
                    ModelePaieRubrique::withoutGlobalScopes()->firstOrCreate(
                        ['modele_id' => $modele->id, 'rubrique_id' => $rubriques[$code]],
                        ['entreprise_id' => self::ENTREPRISE, 'montant_defaut' => 0, 'obligatoire' => in_array($code, ['SAL-BASE', 'CNSS', 'AMU', 'IRPP'], true), 'ordre' => $ordre, 'actif' => true, 'etat' => 1],
                    );
                }
            }
        }
    }

    private function salarie(string $code, string $libelle, string $nom, string $prenoms, string $sexe, string $codePosition, float $salaire, string $embauche): Salarie
    {
        $salarie = Salarie::withoutGlobalScopes()->updateOrCreate(
            ['entreprise_id' => self::ENTREPRISE, 'matricule' => "DEMO-PAIE-{$code}"],
            [
                'nom' => $nom, 'prenoms' => $prenoms, 'sexe' => $sexe, 'date_naissance' => '1988-05-12', 'nationalite' => 'Togolaise',
                'date_embauche' => $embauche, 'date_prise_service' => $embauche, 'type_contrat' => 'CDI',
                'statut_emploi' => StatutEmploi::ACTIF->value, 'statut_dossier' => StatutDossier::COMPLET->value, 'actif' => true, 'etat' => 1,
            ],
        );

        // Remise à l'état de départ.
        foreach (['demandes_conges', 'absences', 'prets_salaries', 'saisies_attribution', 'sorties_salaries', 'mouvements_conge', 'soldes_conges', 'affectations_modele_paie'] as $table) {
            DB::table($table)->where('salarie_id', $salarie->id)->delete();
        }
        DB::table('heures_supplementaires')->where('salarie_id', $salarie->id)->where('periode_paiement_id', $this->periode->id)->delete();
        DB::table('saisies_paie')->where('salarie_id', $salarie->id)->where('periode_id', $this->periode->id)->delete();
        ElementPaieSalarie::withoutGlobalScopes()->where('salarie_id', $salarie->id)->delete();

        $position = PositionClassification::withoutGlobalScopes()->where('code', $codePosition)
            ->whereHas('referentiel', fn ($q) => $q->withoutGlobalScopes()->where('code', InstallateurBaremeCcit2026::CODE_REFERENTIEL))->sole();
        SituationCarriere::withoutGlobalScopes()->updateOrCreate(
            ['salarie_id' => $salarie->id],
            ['entreprise_id' => self::ENTREPRISE, 'position_classification_id' => $position->id, 'date_effet_categorie' => $embauche,
                'date_effet_echelon' => $embauche, 'date_reference_avancement' => $embauche, 'type_source' => 'manual',
                'statut_historique' => 'complete', 'statut_fiabilite' => 'confirmed', 'enregistre_le' => now(), 'etat' => 1],
        );

        $structure = Structure::withoutGlobalScopes()->where('entreprise_id', self::ENTREPRISE)->orderBy('id')->firstOrFail();
        $poste = Poste::withoutGlobalScopes()->updateOrCreate(
            ['entreprise_id' => self::ENTREPRISE, 'code' => "DEMO-PAIE-{$code}"],
            ['structure_id' => $structure->id, 'intitule' => "Démo paie — {$libelle}", 'categorie' => $position->categorie?->libelle ?? 'Employé', 'effectif_cible' => 1, 'actif' => true, 'etat' => 1],
        );
        Affectation::withoutGlobalScopes()->updateOrCreate(
            ['salarie_id' => $salarie->id, 'en_cours' => true],
            ['structure_id' => $structure->id, 'poste_id' => $poste->id, 'date_debut' => $embauche, 'etat' => 1],
        );

        $this->element($salarie, 'SAL-BASE', $salaire);

        return $salarie->fresh();
    }

    /** @param array<string, Salarie> $s */
    private function scenarios(array $s): void
    {
        $jour = fn (int $n) => $this->debut->copy()->addDays($n - 1);
        $type = fn (string $code) => TypeConge::withoutGlobalScopes()->where('entreprise_id', self::ENTREPRISE)->where('code', $code)->sole();
        $demande = fn (Salarie $salarie, string $code, Carbon $du, Carbon $reprise) => DemandeConge::create([
            'entreprise_id' => self::ENTREPRISE, 'numero_demande' => 'DEMO-PAIE-'.$salarie->id.'-'.$code, 'salarie_id' => $salarie->id,
            'type_conge_id' => $type($code)->id, 'date_demande' => $du->copy()->subDays(20), 'date_debut' => $du, 'date_reprise' => $reprise,
            'duree_jours' => $du->diffInDays($reprise), 'statut' => StatutDemandeConge::AUTORISEE->value, 'etat' => 1,
        ]);

        // 01 — transport (remboursement de frais, hors brut) et logement (individuel).
        $this->element($s['01'], 'PRIME-TRANSP', 10000);
        $this->element($s['01'], 'PRIME-LOGT', 20000);

        // 03 — 6 heures à 20 % et 2 heures de nuit un dimanche.
        $taux = round(75500 / 173.33, 4);
        HeureSupplementaire::create([
            'entreprise_id' => self::ENTREPRISE, 'salarie_id' => $s['03']->id, 'reference' => 'DEMO-PAIE-HS-03',
            'debut_travail' => $jour(5), 'fin_travail' => $jour(11), 'periode_paiement_id' => $this->periode->id,
            'heures_hs20' => 6, 'heures_hs40' => 0, 'heures_hs65_jour' => 0, 'heures_hs65_nuit' => 0, 'heures_hs100' => 2,
            'salaire_base_fige' => 75500, 'sursalaire_fige' => 0, 'taux_horaire' => $taux, 'est_rappel' => false, 'motif' => 'Inventaire', 'etat' => 1,
        ]);

        // 04 — 10 jours de congé payé, sur un solde de 30 jours.
        app(GrandLivreConges::class)->ajuster($s['04'], $type('CA'), $this->debut->year - 1, 30, 'Droits de démonstration');
        $demande($s['04'], 'CA', $jour(8), $jour(18));

        // 05 — 20 jours d'arrêt maladie.
        $demande($s['05'], 'MAL', $jour(3), $jour(23));

        // 06 — congé de maternité commencé le 15.
        $demande($s['06'], 'MAT', $jour(15), $jour(15)->addDays(98));

        // 07 — 2 jours d'absence non justifiée.
        Absence::create([
            'entreprise_id' => self::ENTREPRISE, 'salarie_id' => $s['07']->id, 'type_conge_id' => $type('ABS-NJ')->id,
            'debut_le' => $jour(12)->startOfDay(), 'fin_le' => $jour(13)->endOfDay(), 'statut' => 'constatée',
            'qualification' => QualificationAbsence::INJUSTIFIEE->value, 'statut_transmission_paie' => 'a_preparer', 'etat' => 1,
        ]);

        // 08 — avance vérifiée par l'inspecteur et saisie-attribution, plafonnées par la quotité.
        PretSalarie::create([
            'entreprise_id' => self::ENTREPRISE, 'salarie_id' => $s['08']->id, 'type' => 'avance', 'montant' => 100000, 'echeance' => 20000,
            'premier_mois' => $this->debut, 'verification' => 'inspecteur', 'verifie_le' => $this->debut->copy()->subDays(5), 'statut' => 'actif', 'motif' => 'Démonstration',
        ]);
        SaisieAttribution::create([
            'entreprise_id' => self::ENTREPRISE, 'salarie_id' => $s['08']->id, 'creancier' => 'Banque de démonstration',
            'reference_acte' => 'SA-DEMO-08', 'date_acte' => $this->debut->copy()->subDays(10), 'montant_total' => 150000, 'rang' => 1, 'statut' => 'active',
        ]);

        // 09 — cotisation syndicale avec l'accord écrit du salarié.
        $this->element($s['09'], 'COT-SYND', 2000, ['accord_le' => $this->debut->copy()->subMonth()]);

        // 10 — licenciement pour motif personnel le 15, préavis dispensé par l'employeur, 25 jours de congé à solder.
        app(GrandLivreConges::class)->ajuster($s['10'], $type('CA'), $this->debut->year - 1, 25, 'Droits de démonstration');
        SortieSalarie::create([
            'entreprise_id' => self::ENTREPRISE, 'salarie_id' => $s['10']->id, 'date_cessation' => $jour(15),
            'motif' => 'licenciement_personnel', 'preavis' => 'dispense_employeur', 'observations' => 'Démonstration du solde de tout compte',
        ]);
    }

    private function element(Salarie $salarie, string $code, float $montant, array $autres = []): void
    {
        ElementPaieSalarie::withoutGlobalScopes()->updateOrCreate(
            ['salarie_id' => $salarie->id, 'rubrique_id' => RubriquePaie::withoutGlobalScopes()->where('entreprise_id', self::ENTREPRISE)->where('code', $code)->value('id')],
            $autres + ['entreprise_id' => self::ENTREPRISE, 'montant' => $montant, 'actif' => true, 'etat' => 1],
        );
    }
}
