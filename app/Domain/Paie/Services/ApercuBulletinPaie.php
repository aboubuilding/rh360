<?php

namespace App\Domain\Paie\Services;

use App\Domain\Classification\Models\PositionClassification;
use App\Domain\Paie\Enums\NatureRubrique;
use App\Domain\Paie\Enums\TraitementFiscal;
use App\Domain\Paie\Models\BulletinPaie;
use App\Domain\Paie\Models\LigneBulletin;
use App\Domain\Paie\Models\ModelePaie;
use App\Domain\Paie\Models\PeriodePaie;
use App\Domain\Paie\Models\RegleAnciennete;
use App\Domain\Paie\Models\RegleIrpp;
use App\Domain\Paie\Models\RubriquePaie;
use App\Domain\Personnel\Models\Salarie;
use Carbon\Carbon;

/**
 * Aperçu d'un modèle de bulletin, sans rien enregistrer :
 * - pour un salarié réel : le moteur de paie calcule son bulletin du mois avec ce modèle ;
 * - bulletin type : un salarié fictif payé au minimum de la catégorie du modèle, avec les
 *   montants par défaut des rubriques du modèle et l'ancienneté choisie.
 * Les cotisations et l'impôt suivent les paramètres en vigueur à la date du mois.
 */
class ApercuBulletinPaie
{
    private const RUBRIQUES_SYSTEME = ['SAL-BASE', 'PRIME-ANC', 'HS', 'RAPPEL', 'CNSS', 'AMU', 'IRPP'];

    public function __construct(
        private MoteurPaie $moteur,
        private CalculateurCotisations $cotisations,
    ) {}

    /**
     * @return array{bulletin: BulletinPaie, anomalies: array<int, array{gravite: string, code: string, message: string}>}
     */
    public function pourSalarie(ModelePaie $modele, Salarie $salarie, Carbon $mois): array
    {
        $calcul = $this->moteur->construire($modele->entreprise_id, null, $salarie, $mois, $modele);

        return [
            'bulletin' => $this->bulletin($modele->entreprise_id, $calcul['bulletin'], $calcul['lignes'], $salarie, $mois),
            'anomalies' => $calcul['anomalies'],
        ];
    }

    /**
     * @return array{bulletin: BulletinPaie, anomalies: array<int, array{gravite: string, code: string, message: string}>}
     */
    public function type(ModelePaie $modele, Carbon $mois, int $anciennete): array
    {
        $date = $mois->copy()->endOfMonth()->startOfDay();
        $anomalies = [];

        // Salarié fictif payé au minimum de la catégorie du modèle (échelon le plus bas).
        $position = PositionClassification::withoutGlobalScopes()
            ->where('categorie_id', $modele->categorie_id)
            ->whereNotNull('salaire_minimum')
            ->with(['categorie', 'echelon'])
            ->orderBy('salaire_minimum')
            ->first();
        $base = (float) ($position?->salaire_minimum ?? 0);
        if ($base <= 0) {
            $anomalies[] = ['gravite' => 'alerte', 'code' => 'bareme', 'message' => 'Aucun salaire minimum au barème pour la catégorie du modèle : salaire de base à 0.'];
        }

        $rubriques = RubriquePaie::pourEntreprise($modele->entreprise_id)->get()->keyBy('code');
        $lignes = [$this->ligne($rubriques->get('SAL-BASE'), 'SAL-BASE', 'Salaire de base', NatureRubrique::GAIN->value, $base, TraitementFiscal::IMPOSABLE->value, 1)];

        foreach ($modele->rubriquesActives()->with('rubrique')->get() as $ligneModele) {
            $rubrique = $ligneModele->rubrique;
            if (! $rubrique || ! $rubrique->actif || in_array($rubrique->code, self::RUBRIQUES_SYSTEME, true) || (float) $ligneModele->montant_defaut == 0.0) {
                continue;
            }
            $lignes[] = $this->ligne($rubrique, $rubrique->code, $rubrique->nom, $rubrique->nature->value, (float) $ligneModele->montant_defaut,
                $rubrique->traitement_fiscal->value, 10, (bool) $rubrique->est_frais, (bool) $rubrique->soumis_cotisation);
        }

        $regleAnciennete = RegleAnciennete::pourEntreprise($modele->entreprise_id)->enVigueur($date)->orderByDesc('debut_effet')->first();
        $taux = $regleAnciennete ? $regleAnciennete->calculerTaux($anciennete) : 0.0;
        if ($taux > 0 && $base > 0) {
            $lignes[] = $this->ligne($rubriques->get('PRIME-ANC'), 'PRIME-ANC', "Prime d'ancienneté ({$anciennete} ans, ".rtrim(rtrim(number_format($taux, 2, ',', ''), '0'), ',').' %)',
                NatureRubrique::GAIN->value, $base * $taux / 100, TraitementFiscal::IMPOSABLE->value, 15);
        }

        $gains = collect($lignes)->where('nature', NatureRubrique::GAIN->value);
        $brut = $gains->reject(fn ($l) => $l['frais'])->sum('montant');
        $frais = $gains->filter(fn ($l) => $l['frais'])->sum('montant');
        $assiette = $gains->reject(fn ($l) => $l['frais'])->filter(fn ($l) => $l['cotisable'])->sum('montant');
        $brutImposable = $gains->reject(fn ($l) => $l['frais'])->where('traitement_fiscal', TraitementFiscal::IMPOSABLE->value)->sum('montant');

        $cotisations = $this->cotisations->calculer($modele->entreprise_id, $assiette, $date);
        foreach ($cotisations['lignes'] as $i => $c) {
            $lignes[] = $this->ligne($rubriques->get($c['code']), $c['code'], $c['nom'], NatureRubrique::COTISATION->value, $c['montant'], TraitementFiscal::IMPOSABLE->value, 60 + $i);
        }
        foreach ($cotisations['patronales'] as $i => $c) {
            $lignes[] = $this->ligne(null, $c['code'], $c['nom'], NatureRubrique::COTISATION_PATRONALE->value, $c['montant'], TraitementFiscal::EXONERE->value, 70 + $i);
        }
        $deductibles = $this->cotisations->cotisationsDeductibles($modele->entreprise_id, $assiette, $date);

        // Impôt sans charges de famille, avec l'abattement professionnel en vigueur.
        $regleIrpp = RegleIrpp::pourEntreprise($modele->entreprise_id)->enVigueur($date)->first();
        $abattement = $regleIrpp ? $regleIrpp->calculerAbattement($brutImposable) : 0.0;
        $baseImposable = max(0, round($brutImposable - $deductibles - $abattement, 2));
        $irpp = $regleIrpp ? $regleIrpp->calculerIrpp($baseImposable) : 0.0;
        if ($irpp > 0) {
            $lignes[] = $this->ligne($rubriques->get('IRPP'), 'IRPP', 'Impôt sur le revenu (IRPP)', NatureRubrique::RETENUE->value, $irpp, TraitementFiscal::EXONERE->value, 90);
        }

        $retenues = $cotisations['total_salarial'] + $irpp;
        $salarie = new Salarie([
            'nom' => 'SALARIÉ',
            'prenoms' => 'Type',
            'matricule' => 'APERÇU',
            'date_embauche' => $date->copy()->subYears($anciennete),
        ]);

        return [
            'bulletin' => $this->bulletin($modele->entreprise_id, [
                'montant_brut' => round($brut, 2),
                'assiette_cotisations' => round($assiette, 2),
                'montant_retenues' => round($retenues, 2),
                'montant_frais' => round($frais, 2),
                'montant_net' => round($brut - $retenues + $frais, 2),
                'montant_cotisations_patronales' => $cotisations['total_patronal'],
                'brut_imposable' => round($brutImposable, 2),
                'retenues_sociales_deductibles' => $deductibles,
                'abattement_professionnel' => $abattement,
                'deduction_charges_famille' => 0,
                'base_imposable' => $baseImposable,
                'montant_irpp' => $irpp,
                'jours_presence' => $date->daysInMonth,
                'jours_mois' => $date->daysInMonth,
                'categorie_libelle' => $position?->categorie ? trim($position->categorie->code.' '.$position->categorie->libelle) : $modele->categorie?->libelle,
                'echelon_libelle' => $position?->echelon?->code,
                'emploi_libelle' => 'Emploi de la catégorie',
            ], $lignes, $salarie, $mois),
            'anomalies' => $anomalies,
        ];
    }

    /**
     * Bulletin non enregistré, prêt à être affiché avec le gabarit du bulletin de paie.
     *
     * @param  array<string, mixed>  $attributs
     * @param  array<int, array<string, mixed>>  $lignes
     */
    private function bulletin(int $entrepriseId, array $attributs, array $lignes, Salarie $salarie, Carbon $mois): BulletinPaie
    {
        $rubriques = RubriquePaie::pourEntreprise($entrepriseId)->get()->keyBy('id');

        $bulletin = new BulletinPaie($attributs + ['entreprise_id' => $entrepriseId]);
        $bulletin->setRelation('salarie', $salarie);
        $bulletin->setRelation('periode', new PeriodePaie(['entreprise_id' => $entrepriseId, 'annee' => $mois->year, 'mois' => $mois->month]));
        $bulletin->setRelation('lignes', collect($lignes)->sortBy('ordre')->values()->map(function (array $l) use ($rubriques) {
            $ligne = new LigneBulletin(array_diff_key($l, ['frais' => true, 'cotisable' => true]));

            return $ligne->setRelation('rubrique', $rubriques->get($l['rubrique_id']));
        }));

        return $bulletin;
    }

    private function ligne(?RubriquePaie $rubrique, string $code, string $libelle, string $nature, float $montant, string $traitement, int $ordre, bool $frais = false, bool $cotisable = true): array
    {
        return [
            'rubrique_id' => $rubrique?->id,
            'code' => $code,
            'libelle' => $libelle,
            'nature' => $nature,
            'montant' => round($montant, 2),
            'traitement_fiscal' => $traitement,
            'montant_imposable' => $traitement === TraitementFiscal::IMPOSABLE->value && ! $frais ? round($montant, 2) : 0,
            'ordre' => $ordre,
            'frais' => $frais,
            'cotisable' => $cotisable,
        ];
    }
}
