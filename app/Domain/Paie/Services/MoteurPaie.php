<?php

namespace App\Domain\Paie\Services;

use App\Domain\Paie\Enums\NatureRubrique;
use App\Domain\Paie\Enums\StatutPeriode;
use App\Domain\Paie\Enums\TraitementFiscal;
use App\Domain\Paie\Events\BulletinCalcule;
use App\Domain\Paie\Models\BulletinPaie;
use App\Domain\Paie\Models\ElementPaieSalarie;
use App\Domain\Paie\Models\HeureSupplementaire;
use App\Domain\Paie\Models\LigneBulletin;
use App\Domain\Paie\Models\PeriodePaie;
use App\Domain\Paie\Models\RappelAvancement;
use App\Domain\Paie\Models\RubriquePaie;
use App\Domain\Paie\Models\SaisiePaie;
use App\Domain\Personnel\Models\Salarie;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MoteurPaie
{
    public function __construct(
        private CalculateurAnciennete $calcAnciennete,
        private CalculateurCotisations $calcCotisations,
        private CalculateurHeuresSupp $calcHS,
        private CalculateurIRPP $calcIrpp,
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
            $dateDebutPeriode = Carbon::createFromDate($periode->annee, $periode->mois, 1);
            $dateFinPeriode = $dateDebutPeriode->copy()->endOfMonth();
            $dateReference = $dateFinPeriode;

            // Récupérer tous les salariés actifs de l'entreprise à cette date
            $salaries = Salarie::where('entreprise_id', $periode->entreprise_id)
                ->where('actif', true)
                ->where(function ($q) use ($dateFinPeriode) {
                    $q->whereNull('date_embauche')
                      ->orWhere('date_embauche', '<=', $dateFinPeriode);
                })
                ->get();

            $resultats = [
                'periode_id' => $periode->id,
                'bulletins_calcules' => 0,
                'total_brut' => 0,
                'total_net' => 0,
            ];

            foreach ($salaries as $salarie) {
                try {
                    $bulletin = $this->calculerBulletin($periode, $salarie, $dateReference);
                    $resultats['bulletins_calcules']++;
                    $resultats['total_brut'] += (float) $bulletin->montant_brut;
                    $resultats['total_net'] += (float) $bulletin->montant_net;
                } catch (\Throwable $e) {
                    \Log::error("Erreur calcul bulletin salarié {$salarie->id} période {$periode->id}: " . $e->getMessage());
                }
            }

            $periode->update(['statut' => StatutPeriode::CALCULEE->value]);

            return $resultats;
        });
    }

    /**
     * Calcule le bulletin d'un salarié pour une période.
     */
    private function calculerBulletin(PeriodePaie $periode, Salarie $salarie, Carbon $date): BulletinPaie
    {
        $lignes = [];

        // ============================================================
        // 1. BASE : salaire de base + éléments fixes du salarié
        // ============================================================
        $salaireBase = $this->recupererSalaireBase($salarie);
        $lignes[] = $this->ligne('SAL-BASE', 'Salaire de base', NatureRubrique::GAIN->value, $salaireBase, TraitementFiscal::IMPOSABLE->value, 1);

        // Éléments fixes (primes récurrentes)
        foreach ($this->elementsFixesActifs($salarie) as $element) {
            $lignes[] = $this->ligne(
                $element->rubrique->code,
                $element->rubrique->nom,
                $element->rubrique->nature->value,
                (float) $element->montant,
                $element->rubrique->traitement_fiscal->value,
                10
            );
        }

        // ============================================================
        // 2. HEURES SUPPLÉMENTAIRES
        // ============================================================
        $totalHS = $this->calcHS->totalPeriode($periode->entreprise_id, $periode->id);
        if ($totalHS > 0) {
            $lignes[] = $this->ligne('HS', 'Heures supplémentaires', NatureRubrique::GAIN->value, $totalHS, TraitementFiscal::IMPOSABLE->value, 20);
        }

        // ============================================================
        // 3. RAPPELS D'AVANCEMENT
        // ============================================================
        $totalRappels = $this->recupererRappelsPeriode($periode->id);
        if ($totalRappels > 0) {
            $lignes[] = $this->ligne('RAPPEL', 'Rappel d\'avancement', NatureRubrique::GAIN->value, $totalRappels, TraitementFiscal::IMPOSABLE->value, 25);
        }

        // ============================================================
        // 4. SAISIES VARIABLES DE LA PÉRIODE
        // ============================================================
        $saisies = SaisiePaie::where('periode_id', $periode->id)
            ->where('salarie_id', $salarie->id)
            ->with('rubrique')
            ->get();

        foreach ($saisies as $saisie) {
            if (! $saisie->rubrique) continue;
            $lignes[] = $this->ligne(
                $saisie->rubrique->code,
                $saisie->rubrique->nom,
                $saisie->rubrique->nature->value,
                (float) $saisie->montant,
                $saisie->rubrique->traitement_fiscal->value,
                30
            );
        }

        // ============================================================
        // 5. PRIME D'ANCIENNETÉ (calculée sur le salaire de base)
        // ============================================================
        $primeAnciennete = $this->calcAnciennete->calculerPrime($salarie, $salaireBase, $date);
        if ($primeAnciennete > 0) {
            $lignes[] = $this->ligne('PRIME-ANC', 'Prime d\'ancienneté', NatureRubrique::GAIN->value, $primeAnciennete, TraitementFiscal::IMPOSABLE->value, 15);
        }

        // ============================================================
        // CALCUL DU BRUT
        // ============================================================
        $brut = collect($lignes)
            ->filter(fn ($l) => $l['nature'] === NatureRubrique::GAIN->value)
            ->sum('montant');

        // Brut imposable = gains imposables uniquement
        $brutImposable = collect($lignes)
            ->filter(fn ($l) => $l['nature'] === NatureRubrique::GAIN->value && $l['traitement_fiscal'] === TraitementFiscal::IMPOSABLE->value)
            ->sum('montant');

        // ============================================================
        // 6. COTISATIONS SOCIALES
        // ============================================================
        $cotisations = $this->calcCotisations->calculer($periode->entreprise_id, $brut, $date);
        foreach ($cotisations['lignes'] as $i => $cot) {
            $lignes[] = $this->ligne($cot['code'], $cot['nom'], NatureRubrique::COTISATION->value, $cot['montant'], TraitementFiscal::IMPOSABLE->value, 60 + $i);
        }
        $totalCotisations = $cotisations['total_salarial'];

        // Cotisations déductibles (CNSS + AMU uniquement)
        $cotisationsDeductibles = $this->calcCotisations->cotisationsDeductibles($periode->entreprise_id, $brut, $date);

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
        $net = $brut - $totalRetenues;

        // ============================================================
        // ENREGISTREMENT
        // ============================================================
        $bulletin = BulletinPaie::updateOrCreate(
            [
                'periode_id' => $periode->id,
                'salarie_id' => $salarie->id,
            ],
            [
                'entreprise_id' => $periode->entreprise_id,
                'montant_brut' => round($brut, 2),
                'montant_retenues' => round($totalRetenues, 2),
                'montant_net' => round($net, 2),
                'brut_imposable' => $resultatIrpp['brut_imposable'],
                'retenues_sociales_deductibles' => $resultatIrpp['cotisations_deductibles'],
                'abattement_professionnel' => $resultatIrpp['abattement_professionnel'],
                'deduction_charges_famille' => $resultatIrpp['deduction_charges'],
                'base_imposable' => $resultatIrpp['base_imposable'],
                'montant_irpp' => $resultatIrpp['montant_irpp'],
                'calcule_le' => now(),
                'etat' => 1,
            ]
        );

        // Supprimer les anciennes lignes et recréer
        $bulletin->lignes()->delete();
        foreach ($lignes as $ligne) {
            LigneBulletin::create(array_merge($ligne, [
                'entreprise_id' => $periode->entreprise_id,
                'bulletin_id' => $bulletin->id,
            ]));
        }

        event(new BulletinCalcule($bulletin));

        return $bulletin->fresh(['lignes']);
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
        int $ordre
    ): array {
        return [
            'rubrique_id' => null,
            'code' => $code,
            'libelle' => $libelle,
            'nature' => $nature,
            'montant' => round($montant, 2),
            'traitement_fiscal' => $traitementFiscal,
            'montant_imposable' => $traitementFiscal === TraitementFiscal::IMPOSABLE->value ? round($montant, 2) : 0,
            'ordre' => $ordre,
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

    private function elementsFixesActifs(Salarie $salarie)
    {
        return ElementPaieSalarie::where('salarie_id', $salarie->id)
            ->where('actif', true)
            ->whereHas('rubrique', fn ($q) => $q->where('code', '!=', 'SAL-BASE')->where('actif', true))
            ->with('rubrique')
            ->get();
    }

    private function recupererRappelsPeriode(int $periodeId): float
    {
        return (float) RappelAvancement::where('periode_generation_id', $periodeId)->get()
            ->sum(fn ($r) => $r->montantTotal());
    }
}