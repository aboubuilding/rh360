<?php

namespace App\Domain\Paie\Services;

use App\Domain\Carriere\Models\SituationCarriere;
use App\Domain\Interim\Services\PreparateurPaieInterims;
use App\Domain\Interim\Services\PreparateurPaieReglesConnexes;
use App\Domain\Paie\Enums\NatureRubrique;
use App\Domain\Paie\Enums\RecurrenceRubrique;
use App\Domain\Paie\Enums\StatutPeriode;
use App\Domain\Paie\Enums\TraitementFiscal;
use App\Domain\Paie\Events\BulletinCalcule;
use App\Domain\Paie\Models\AnomaliePaie;
use App\Domain\Paie\Models\BulletinPaie;
use App\Domain\Paie\Models\ElementPaieSalarie;
use App\Domain\Paie\Models\HeureSupplementaire;
use App\Domain\Paie\Models\LigneBulletin;
use App\Domain\Paie\Models\ParametreLegalPaie;
use App\Domain\Paie\Models\PeriodePaie;
use App\Domain\Paie\Models\RappelAvancement;
use App\Domain\Paie\Models\RubriquePaie;
use App\Domain\Paie\Models\SaisiePaie;
use App\Domain\Personnel\Models\Salarie;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Calcul de la paie d'une période.
 *
 * Pour chaque salarié : salaire de base et éléments fixes au prorata de la présence du mois,
 * heures supplémentaires et rappels du salarié, saisies de la période, prime d'ancienneté ;
 * puis brut (hors remboursements de frais, Code art. 162), cotisations salariales sur leur
 * assiette, parts patronales affichées sans être déduites (CCIT art. 35), impôt, retenues et net.
 *
 * Les contrôles (salaire de base, minimum de la catégorie, SMIG, catégorie et emploi, erreur de
 * calcul) produisent des anomalies : une anomalie bloquante empêche la validation de la période.
 */
class MoteurPaie
{
    /** @var array<string, int> identifiants des rubriques par code */
    private array $rubriques = [];

    /** @var array<int, array{gravite: string, code: string, message: string}> anomalies du bulletin en cours */
    private array $anomalies = [];

    // Rubriques calculées par le moteur lui-même : un modèle ou un élément individuel ne les remplace pas.
    private const RUBRIQUES_SYSTEME = ['SAL-BASE', 'PRIME-ANC', 'HS', 'RAPPEL', 'CNSS', 'AMU', 'IRPP'];

    public function __construct(
        private CalculateurAnciennete $calcAnciennete,
        private CalculateurCotisations $calcCotisations,
        private CalculateurHeuresSupp $calcHS,
        private CalculateurIRPP $calcIrpp,
        private ResolveurModelePaie $resolveurModele,
    ) {}

    /**
     * Calcule la paie complète d'une période : génère ou met à jour tous les bulletins.
     */
    public function calculer(PeriodePaie $periode): array
    {
        if (! $periode->peutEtreCalculee()) {
            throw new \DomainException('Cette période est figée et ne peut plus être calculée.');
        }

        return DB::transaction(function () use ($periode) {
            // RG-INT-06 / RG-INT-07 — indemnité et avantages d'intérim, recalculés en saisies de la période.
            $rapportInterims = app(PreparateurPaieInterims::class)->preparer($periode);
            // RG-INF-01 à 03 / RG-REM-04 — maintien de salaire et indemnité de fin de CDD.
            $rapportConnexes = app(PreparateurPaieReglesConnexes::class)->preparer($periode);

            $dateFinPeriode = Carbon::createFromDate($periode->annee, $periode->mois, 1)->endOfMonth()->startOfDay();
            $this->rubriques = RubriquePaie::pourEntreprise($periode->entreprise_id)->pluck('id', 'code')->all();

            // Les anomalies sont celles du dernier calcul.
            AnomaliePaie::pourEntreprise($periode->entreprise_id)->where('periode_id', $periode->id)->delete();

            $salaries = Salarie::where('entreprise_id', $periode->entreprise_id)
                ->where('actif', true)
                ->where(fn ($q) => $q->whereNull('date_embauche')->orWhere('date_embauche', '<=', $dateFinPeriode))
                ->get();

            $resultats = [
                'periode_id' => $periode->id,
                'bulletins_calcules' => 0,
                'total_brut' => 0,
                'total_net' => 0,
                'anomalies_bloquantes' => 0,
                'anomalies_interims' => array_merge($rapportInterims->anomalies, $rapportConnexes->anomalies),
            ];

            foreach ($salaries as $salarie) {
                try {
                    $bulletin = $this->calculerBulletin($periode, $salarie, $dateFinPeriode);
                    $resultats['bulletins_calcules']++;
                    $resultats['total_brut'] += (float) $bulletin->montant_brut;
                    $resultats['total_net'] += (float) $bulletin->montant_net;
                } catch (\Throwable $e) {
                    // Un bulletin non calculé n'est jamais ignoré : il bloque la validation.
                    \Log::error("Erreur calcul bulletin salarié {$salarie->id} période {$periode->id}: ".$e->getMessage());
                    $this->anomalies = [['gravite' => AnomaliePaie::BLOQUANTE, 'code' => 'calcul', 'message' => 'Bulletin non calculé : '.$e->getMessage()]];
                    $this->enregistrerAnomalies($periode, $salarie);
                }
            }

            $resultats['anomalies_bloquantes'] = AnomaliePaie::pourEntreprise($periode->entreprise_id)
                ->where('periode_id', $periode->id)->where('gravite', AnomaliePaie::BLOQUANTE)->count();

            $periode->update(['statut' => StatutPeriode::CALCULEE->value]);

            return $resultats;
        });
    }

    /**
     * Calcule et enregistre le bulletin d'un salarié pour une période, avec ses anomalies.
     */
    private function calculerBulletin(PeriodePaie $periode, Salarie $salarie, Carbon $date): BulletinPaie
    {
        $calcul = $this->construire($periode->entreprise_id, $periode->id, $salarie, $date);

        $bulletin = BulletinPaie::updateOrCreate(
            ['periode_id' => $periode->id, 'salarie_id' => $salarie->id],
            $calcul['bulletin'] + ['entreprise_id' => $periode->entreprise_id, 'calcule_le' => now(), 'etat' => 1],
        );

        // Supprimer les anciennes lignes et recréer
        $bulletin->lignes()->delete();
        foreach ($calcul['lignes'] as $ligne) {
            unset($ligne['frais'], $ligne['cotisable']);
            LigneBulletin::create(array_merge($ligne, [
                'entreprise_id' => $periode->entreprise_id,
                'bulletin_id' => $bulletin->id,
            ]));
        }

        $this->anomalies = $calcul['anomalies'];
        $this->enregistrerAnomalies($periode, $salarie);

        event(new BulletinCalcule($bulletin));

        return $bulletin->fresh(['lignes']);
    }

    /**
     * Calcule le bulletin d'un salarié pour le mois qui se termine à la date donnée, sans rien
     * enregistrer (calcul de la période, ou aperçu). Sans période, ni heures supplémentaires,
     * ni rappels, ni saisies variables ne sont pris en compte. Un modèle imposé (aperçu d'un
     * modèle pour un salarié) remplace le modèle du salarié.
     *
     * @return array{bulletin: array<string, mixed>, lignes: array<int, array<string, mixed>>, anomalies: array<int, array{gravite: string, code: string, message: string}>}
     */
    public function construire(int $entrepriseId, ?int $periodeId, Salarie $salarie, Carbon $date, ?\App\Domain\Paie\Models\ModelePaie $modeleForce = null): array
    {
        $this->anomalies = [];
        $this->rubriques = RubriquePaie::pourEntreprise($entrepriseId)->pluck('id', 'code')->all();
        $date = $date->copy()->endOfMonth()->startOfDay();
        $lignes = [];

        // ============================================================
        // 0. PRÉSENCE DU MOIS, CATÉGORIE, EMPLOI ET MINIMA
        // ============================================================
        $debutPeriode = $date->copy()->startOfMonth();
        $joursMois = $date->daysInMonth;
        $debutPresence = $salarie->date_embauche && $salarie->date_embauche->gt($debutPeriode)
            ? $salarie->date_embauche->copy()->startOfDay()
            : $debutPeriode;
        $joursPresence = (int) $debutPresence->diffInDays($date) + 1;
        $prorata = $joursPresence / $joursMois;

        $position = SituationCarriere::withoutGlobalScopes()->where('salarie_id', $salarie->id)
            ->first()?->positionClassification()->with(['categorie', 'echelon'])->first();
        $emploi = $salarie->affectationCourante?->poste?->intitule;

        $salaireBase = $this->recupererSalaireBase($salarie);
        $this->controlerMinima($entrepriseId, $salaireBase, $position, $emploi, $date);

        // Modèle de bulletin : association manuelle en vigueur, sinon celui de la catégorie.
        $resolution = $modeleForce
            ? ['modele' => $modeleForce, 'mode' => 'apercu', 'anomalie' => null]
            : $this->resolveurModele->pour($salarie, $date);
        if ($resolution['anomalie']) {
            $this->signaler(AnomaliePaie::BLOQUANTE, 'modele', $resolution['anomalie']);
        }

        // ============================================================
        // 1. BASE : salaire de base + éléments fixes du salarié, au prorata de la présence
        // ============================================================
        $lignes[] = $this->ligne(
            'SAL-BASE',
            $prorata < 1 ? "Salaire de base ({$joursPresence}/{$joursMois} jours)" : 'Salaire de base',
            NatureRubrique::GAIN->value, $salaireBase * $prorata, TraitementFiscal::IMPOSABLE->value, 1,
        );

        foreach ($this->composerElements($salarie, $resolution['modele'], $debutPresence, $date) as [$rubrique, $montant]) {
            $lignes[] = $this->ligne(
                $rubrique->code, $rubrique->nom, $rubrique->nature->value, $montant,
                $rubrique->traitement_fiscal->value, 10, (bool) $rubrique->est_frais, (bool) $rubrique->soumis_cotisation,
            );
        }

        // ============================================================
        // 2. HEURES SUPPLÉMENTAIRES DU SALARIÉ
        // ============================================================
        $totalHS = $periodeId ? $this->heuresSupplementaires($salarie, $entrepriseId, $periodeId) : 0;
        if ($totalHS > 0) {
            $lignes[] = $this->ligne('HS', 'Heures supplémentaires', NatureRubrique::GAIN->value, $totalHS, TraitementFiscal::IMPOSABLE->value, 20);
        }

        // ============================================================
        // 3. RAPPELS D'AVANCEMENT DU SALARIÉ
        // ============================================================
        $totalRappels = $periodeId ? $this->rappels($salarie, $periodeId) : 0;
        if ($totalRappels > 0) {
            $lignes[] = $this->ligne('RAPPEL', 'Rappel d\'avancement', NatureRubrique::GAIN->value, $totalRappels, TraitementFiscal::IMPOSABLE->value, 25);
        }

        // ============================================================
        // 4. SAISIES VARIABLES DE LA PÉRIODE
        // ============================================================
        $saisies = $periodeId
            ? SaisiePaie::where('periode_id', $periodeId)->where('salarie_id', $salarie->id)->with('rubrique')->get()
            : collect();

        foreach ($saisies as $saisie) {
            if (! $saisie->rubrique) {
                continue;
            }
            $lignes[] = $this->ligne(
                $saisie->rubrique->code, $saisie->rubrique->nom, $saisie->rubrique->nature->value, (float) $saisie->montant,
                $saisie->rubrique->traitement_fiscal->value, 30, (bool) $saisie->rubrique->est_frais, (bool) $saisie->rubrique->soumis_cotisation,
            );
        }

        // ============================================================
        // 5. PRIME D'ANCIENNETÉ (au prorata de la présence)
        // ============================================================
        $primeAnciennete = $this->calcAnciennete->calculerPrime($salarie, $salaireBase, $date) * $prorata;
        if ($primeAnciennete > 0) {
            $lignes[] = $this->ligne('PRIME-ANC', 'Prime d\'ancienneté', NatureRubrique::GAIN->value, $primeAnciennete, TraitementFiscal::IMPOSABLE->value, 15);
        }

        // ============================================================
        // BRUT, FRAIS ET ASSIETTES
        // ============================================================
        $gains = collect($lignes)->filter(fn ($l) => $l['nature'] === NatureRubrique::GAIN->value);
        $brut = $gains->reject(fn ($l) => $l['frais'])->sum('montant');
        // Remboursements de frais : hors brut et hors assiettes, ajoutés au net.
        $frais = $gains->filter(fn ($l) => $l['frais'])->sum('montant');
        $assietteCotisations = $gains->reject(fn ($l) => $l['frais'])->filter(fn ($l) => $l['cotisable'])->sum('montant');
        $brutImposable = $gains->reject(fn ($l) => $l['frais'])
            ->filter(fn ($l) => $l['traitement_fiscal'] === TraitementFiscal::IMPOSABLE->value)
            ->sum('montant');

        // ============================================================
        // 6. COTISATIONS SOCIALES (parts salariales déduites, parts patronales affichées)
        // ============================================================
        $cotisations = $this->calcCotisations->calculer($entrepriseId, $assietteCotisations, $date);
        foreach ($cotisations['lignes'] as $i => $cot) {
            $lignes[] = $this->ligne($cot['code'], $cot['nom'], NatureRubrique::COTISATION->value, $cot['montant'], TraitementFiscal::IMPOSABLE->value, 60 + $i);
        }
        foreach ($cotisations['patronales'] as $i => $cot) {
            $lignes[] = $this->ligne($cot['code'], $cot['nom'], NatureRubrique::COTISATION_PATRONALE->value, $cot['montant'], TraitementFiscal::EXONERE->value, 70 + $i);
        }
        $totalCotisations = $cotisations['total_salarial'];

        // Cotisations déductibles (CNSS + AMU uniquement)
        $cotisationsDeductibles = $this->calcCotisations->cotisationsDeductibles($entrepriseId, $assietteCotisations, $date);

        // ============================================================
        // 7. CALCUL IRPP
        // ============================================================
        $resultatIrpp = $this->calcIrpp->calculer($salarie, $brutImposable, $cotisationsDeductibles, $date);

        if ($resultatIrpp['montant_irpp'] > 0) {
            $lignes[] = $this->ligne('IRPP', 'Impôt sur le revenu (IRPP)', NatureRubrique::RETENUE->value, $resultatIrpp['montant_irpp'], TraitementFiscal::EXONERE->value, 90);
        }

        // ============================================================
        // AUTRES RETENUES (saisies de nature RETENUE)
        // ============================================================
        $autresRetenues = collect($lignes)
            ->filter(fn ($l) => $l['nature'] === NatureRubrique::RETENUE->value && $l['code'] !== 'IRPP')
            ->sum('montant');

        // ============================================================
        // CALCUL DU NET
        // ============================================================
        $totalRetenues = $totalCotisations + $resultatIrpp['montant_irpp'] + $autresRetenues;
        $net = $brut - $totalRetenues + $frais;

        return [
            'bulletin' => [
                'montant_brut' => round($brut, 2),
                'assiette_cotisations' => round($assietteCotisations, 2),
                'montant_retenues' => round($totalRetenues, 2),
                'montant_frais' => round($frais, 2),
                'montant_net' => round($net, 2),
                'montant_cotisations_patronales' => $cotisations['total_patronal'],
                'brut_imposable' => $resultatIrpp['brut_imposable'],
                'retenues_sociales_deductibles' => $resultatIrpp['cotisations_deductibles'],
                'abattement_professionnel' => $resultatIrpp['abattement_professionnel'],
                'deduction_charges_famille' => $resultatIrpp['deduction_charges'],
                'base_imposable' => $resultatIrpp['base_imposable'],
                'montant_irpp' => $resultatIrpp['montant_irpp'],
                'jours_presence' => $joursPresence,
                'jours_mois' => $joursMois,
                'categorie_libelle' => $position?->categorie ? trim(($position->categorie->code ?? '').' '.($position->categorie->libelle ?? $position->categorie->nom ?? '')) : null,
                'echelon_libelle' => $position?->echelon?->code,
                'emploi_libelle' => $emploi,
            ],
            'lignes' => $lignes,
            'anomalies' => $this->anomalies,
        ];
    }

    // ============================================================
    // CONTRÔLES
    // ============================================================

    /**
     * Salaire de base présent, au moins égal au minimum de la catégorie et de l'échelon et au
     * SMIG (CCIT art. 10, 28 ; Annexe II) ; catégorie et emploi connus pour le bulletin (CCIT art. 35).
     */
    private function controlerMinima(int $entrepriseId, float $salaireBase, $position, ?string $emploi, Carbon $date): void
    {
        if ($salaireBase <= 0) {
            $this->signaler(AnomaliePaie::BLOQUANTE, 'salaire_base', 'Salaire de base non renseigné.');
        }

        if (! $position) {
            $this->signaler(AnomaliePaie::BLOQUANTE, 'categorie', 'Catégorie professionnelle non renseignée : elle doit figurer sur le bulletin.');
        } elseif ($position->salaire_minimum !== null && $salaireBase > 0 && $salaireBase < (float) $position->salaire_minimum) {
            $this->signaler(AnomaliePaie::BLOQUANTE, 'minimum_categorie', sprintf(
                'Salaire de base de %s FCFA inférieur au minimum de la catégorie %s, échelon %s (%s FCFA).',
                $this->fcfa($salaireBase), $position->categorie?->code ?? '—', $position->echelon?->code ?? '—', $this->fcfa((float) $position->salaire_minimum),
            ));
        }

        if (! $emploi) {
            $this->signaler(AnomaliePaie::BLOQUANTE, 'emploi', 'Emploi occupé non renseigné : il doit figurer sur le bulletin.');
        }

        $smig = ParametreLegalPaie::valeur($entrepriseId, 'smig', $date);
        if ($smig !== null && $salaireBase > 0 && $salaireBase < $smig) {
            $this->signaler(AnomaliePaie::BLOQUANTE, 'smig', sprintf(
                'Salaire de base de %s FCFA inférieur au SMIG (%s FCFA).', $this->fcfa($salaireBase), $this->fcfa($smig),
            ));
        }
    }

    private function signaler(string $gravite, string $code, string $message): void
    {
        $this->anomalies[] = ['gravite' => $gravite, 'code' => $code, 'message' => mb_substr($message, 0, 500)];
    }

    private function enregistrerAnomalies(PeriodePaie $periode, Salarie $salarie): void
    {
        foreach ($this->anomalies as $anomalie) {
            AnomaliePaie::create($anomalie + [
                'entreprise_id' => $periode->entreprise_id,
                'periode_id' => $periode->id,
                'salarie_id' => $salarie->id,
            ]);
        }
        $this->anomalies = [];
    }

    // ============================================================
    // HELPERS
    // ============================================================

    private function ligne(
        string $code,
        string $libelle,
        string $nature,
        float $montant,
        string $traitementFiscal,
        int $ordre,
        bool $frais = false,
        bool $cotisable = true,
    ): array {
        return [
            'rubrique_id' => $this->rubriques[$code] ?? null,
            'code' => $code,
            'libelle' => $libelle,
            'nature' => $nature,
            'montant' => round($montant, 2),
            'traitement_fiscal' => $traitementFiscal,
            'montant_imposable' => $traitementFiscal === TraitementFiscal::IMPOSABLE->value && ! $frais ? round($montant, 2) : 0,
            'ordre' => $ordre,
            'frais' => $frais,
            'cotisable' => $cotisable,
        ];
    }

    private function recupererSalaireBase(Salarie $salarie): float
    {
        // Chercher une rubrique système "SAL-BASE" ou fallback sur un élément fixe
        $element = ElementPaieSalarie::where('salarie_id', $salarie->id)
            ->where('actif', true)
            ->whereHas('rubrique', fn ($q) => $q->where('code', 'SAL-BASE'))
            ->first();

        return $element ? (float) $element->montant : 0.0;
    }

    /**
     * Éléments du bulletin hors rubriques calculées par le moteur :
     * - chaque ligne du modèle, au montant individuel du salarié s'il est en vigueur, sinon au
     *   montant par défaut du modèle ; une ligne exclue pour le salarié n'apparaît pas ;
     * - puis les éléments individuels du salarié qui ne figurent pas dans le modèle.
     * Un élément mensuel fixe est proratisé sur les jours où il est en vigueur pendant la présence
     * du mois ; un élément ponctuel ou variable est versé tel quel.
     *
     * @return array<int, array{0: RubriquePaie, 1: float}>
     */
    private function composerElements(Salarie $salarie, ?\App\Domain\Paie\Models\ModelePaie $modele, Carbon $debutPresence, Carbon $fin): array
    {
        $joursMois = $fin->daysInMonth;
        $montant = function (RubriquePaie $rubrique, float $valeur, ?Carbon $debut, ?Carbon $finElement) use ($debutPresence, $fin, $joursMois): float {
            if ($rubrique->recurrence !== RecurrenceRubrique::FIXE) {
                return $valeur;
            }
            $du = $debut && $debut->gt($debutPresence) ? $debut : $debutPresence;
            $au = $finElement && $finElement->lt($fin) ? $finElement : $fin;

            return $au->lt($du) ? 0.0 : $valeur * ((int) $du->diffInDays($au) + 1) / $joursMois;
        };

        $elements = ElementPaieSalarie::where('salarie_id', $salarie->id)
            ->where('actif', true)
            ->with('rubrique')
            ->get()
            ->filter(fn ($e) => $e->rubrique && $e->rubrique->actif && ! in_array($e->rubrique->code, self::RUBRIQUES_SYSTEME, true))
            ->keyBy('rubrique_id');

        $resultat = [];
        foreach ($modele?->rubriquesActives()->with('rubrique')->get() ?? [] as $ligneModele) {
            $rubrique = $ligneModele->rubrique;
            if (! $rubrique || ! $rubrique->actif || in_array($rubrique->code, self::RUBRIQUES_SYSTEME, true)) {
                continue;
            }
            $element = $elements->pull($rubrique->id);
            if ($element?->exclu) {
                continue;
            }
            $valeur = $element && $element->enVigueurEntre($debutPresence, $fin)
                ? $montant($rubrique, (float) $element->montant, $element->debut, $element->fin)
                : $montant($rubrique, (float) $ligneModele->montant_defaut, null, null);
            if ($valeur != 0) {
                $resultat[] = [$rubrique, $valeur];
            }
        }

        foreach ($elements as $element) {
            if ($element->exclu || ! $element->enVigueurEntre($debutPresence, $fin)) {
                continue;
            }
            $valeur = $montant($element->rubrique, (float) $element->montant, $element->debut, $element->fin);
            if ($valeur != 0) {
                $resultat[] = [$element->rubrique, $valeur];
            }
        }

        return $resultat;
    }

    // Heures supplémentaires du salarié payées sur la période.
    private function heuresSupplementaires(Salarie $salarie, int $entrepriseId, int $periodeId): float
    {
        return round(HeureSupplementaire::where('entreprise_id', $entrepriseId)
            ->where('salarie_id', $salarie->id)
            ->pourPeriode($periodeId)
            ->get()
            ->sum(fn ($h) => $h->montantTotal()), 2);
    }

    // Rappels d'avancement du salarié générés sur la période.
    private function rappels(Salarie $salarie, int $periodeId): float
    {
        return (float) RappelAvancement::where('periode_generation_id', $periodeId)
            ->where('salarie_id', $salarie->id)
            ->get()
            ->sum(fn ($r) => $r->montantTotal());
    }

    private function fcfa(float $montant): string
    {
        return number_format($montant, 0, ',', ' ');
    }
}
