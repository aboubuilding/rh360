<?php

namespace App\Domain\Interim\Services;

use App\Domain\Interim\Models\AvantageInterim;
use App\Domain\Interim\Models\InterimInterne;
use App\Domain\Paie\Enums\ModeCalculRubrique;
use App\Domain\Paie\Enums\NatureRubrique;
use App\Domain\Paie\Enums\RecurrenceRubrique;
use App\Domain\Paie\Enums\TraitementFiscal;
use App\Domain\Paie\Models\PeriodePaie;
use App\Domain\Paie\Models\RubriquePaie;
use App\Domain\Paie\Models\SaisiePaie;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Prépare, pour une période de paie non figée, les saisies issues des intérims internes :
 * indemnité d'intérim (RG-INT-06, prorata RG-INT-10) et avantages saisis à la main (RG-INT-07).
 * Les saisies portent la source « interim » et sont recalculées à chaque calcul de la période ;
 * une période validée n'est jamais modifiée.
 */
class PreparateurPaieInterims
{
    public const SOURCE = 'interim';

    public function __construct(
        private IndemniteInterimCalculator $calculateur,
        private ParametresInterim $parametres,
    ) {}

    public function preparer(PeriodePaie $periode): RapportPaieInterims
    {
        if ($periode->estFigee()) {
            throw new \DomainException('Période validée : les éléments d\'intérim ne peuvent plus être modifiés.');
        }

        $this->parametres->pourEntreprise($periode->entreprise_id);
        $rapport = $this->calculer($periode);

        DB::transaction(function () use ($periode, $rapport) {
            // Les saisies d'intérim de la période sont entièrement recalculées.
            SaisiePaie::where('periode_id', $periode->id)->where('source', self::SOURCE)->delete();

            foreach ($rapport->saisies as $saisie) {
                $existante = SaisiePaie::where('periode_id', $periode->id)
                    ->where('salarie_id', $saisie['salarie_id'])
                    ->where('rubrique_id', $saisie['rubrique_id'])
                    ->first();

                // Une saisie manuelle sur la même rubrique n'est jamais écrasée.
                if ($existante) {
                    $rapport->anomalie($saisie['salarie_id'], $saisie['interim_id'],
                        'Une saisie manuelle existe déjà sur la rubrique « '.$saisie['rubrique_nom']
                        .' » : l\'élément d\'intérim de '.number_format($saisie['montant'], 0, ',', ' ').' FCFA n\'a pas été ajouté.');

                    continue;
                }

                (new SaisiePaie)->forceFill([
                    'entreprise_id' => $periode->entreprise_id,
                    'periode_id' => $periode->id,
                    'salarie_id' => $saisie['salarie_id'],
                    'rubrique_id' => $saisie['rubrique_id'],
                    'quantite' => 1,
                    'taux' => 0,
                    'montant' => $saisie['montant'],
                    'observations' => $saisie['observations'],
                    'source' => self::SOURCE,
                    'cree_par' => auth()->id(),
                    'etat' => 1,
                ])->save();
            }
        });

        return $rapport;
    }

    /**
     * Calcule sans rien écrire : saisies attendues et anomalies (affichage de la période).
     */
    public function calculer(PeriodePaie $periode): RapportPaieInterims
    {
        $this->parametres->pourEntreprise($periode->entreprise_id);
        $rapport = new RapportPaieInterims;
        $debutMois = Carbon::create($periode->annee, $periode->mois, 1)->startOfDay();
        $finMois = $debutMois->copy()->endOfMonth()->startOfDay();

        $interims = InterimInterne::withoutGlobalScopes()
            ->where('entreprise_id', $periode->entreprise_id)
            ->where('etat', 1)
            ->where('date_debut', '<=', $finMois)
            ->where(fn ($q) => $q->where('date_fin_reelle', '>=', $debutMois)
                ->orWhere(fn ($q) => $q->whereNull('date_fin_reelle')->where('date_fin_prevue', '>=', $debutMois)))
            ->with(['categorieOccupee', 'posteOccupe', 'avantages.rubrique'])
            ->get();

        $rubrique = null;
        $cumul = [];

        foreach ($interims as $interim) {
            // RG-INT-06 / RG-INT-10
            try {
                $indemnite = $this->calculateur->pourMois($interim, $periode->annee, $periode->mois);
            } catch (IndemniteIncalculable $e) {
                $rapport->anomalie($interim->salarie_id, $interim->id, 'Indemnité d\'intérim non calculée : '.$e->getMessage());
                $indemnite = null;
            }

            if ($indemnite && $indemnite->montant > 0) {
                $rubrique ??= $this->rubriqueIndemnite($periode->entreprise_id);
                $this->cumuler($cumul, $interim, $rubrique, $indemnite->montant,
                    'Indemnité d\'intérim — '.$interim->posteOccupe?->intitule
                    .' : minimum '.number_format($indemnite->minimum, 0, ',', ' ')
                    .' − base '.number_format($indemnite->salaireBase, 0, ',', ' ')
                    .' = '.number_format($indemnite->mensuel, 0, ',', ' ').' FCFA/mois'
                    .', du '.$indemnite->du->format('d/m/Y').' au '.$indemnite->au->format('d/m/Y')
                    .' ('.$indemnite->jours.'/'.$indemnite->joursBase.' j) — CCIT, art. 13');
            }

            // RG-INT-07 — avantages convenus entre les parties, sur les jours couverts du mois.
            foreach ($interim->avantages as $avantage) {
                $montant = $this->montantAvantage($avantage, $interim, $debutMois, $finMois);
                if ($montant > 0) {
                    $this->cumuler($cumul, $interim, $avantage->rubrique, $montant,
                        $avantage->rubrique->nom.' — avantage du poste occupé, accord '.$avantage->reference_accord.' — CCIT, art. 13');
                }
            }
        }

        $rapport->saisies = array_values($cumul);

        return $rapport;
    }

    private function montantAvantage(AvantageInterim $avantage, InterimInterne $interim, Carbon $debutMois, Carbon $finMois): int
    {
        $finAvantage = $avantage->date_fin?->copy()->startOfDay();
        $finInterim = $this->calculateur->finIndemnite($interim);
        $fin = $finAvantage ? $finAvantage->min($finInterim) : $finInterim;

        $du = $avantage->date_debut->copy()->startOfDay()->max($debutMois);
        $au = $fin->min($finMois);

        if ($du->gt($au)) {
            return 0;
        }

        [$jours, $joursBase] = $this->calculateur->joursProrata($du, $au, $debutMois, $finMois);

        return (int) round($avantage->montant_mensuel * $jours / $joursBase);
    }

    /** @param array<string, array<string, mixed>> $cumul */
    private function cumuler(array &$cumul, InterimInterne $interim, RubriquePaie $rubrique, int $montant, string $observation): void
    {
        $cle = $interim->salarie_id.'-'.$rubrique->id;

        $cumul[$cle] ??= [
            'salarie_id' => $interim->salarie_id,
            'interim_id' => $interim->id,
            'rubrique_id' => $rubrique->id,
            'rubrique_nom' => $rubrique->nom,
            'montant' => 0,
            'observations' => '',
        ];

        $cumul[$cle]['montant'] += $montant;
        $cumul[$cle]['observations'] = trim($cumul[$cle]['observations']."\n".$observation);
    }

    /**
     * Rubrique « Indemnité d'intérim », créée au premier besoin. Son traitement fiscal et
     * social se règle dans l'écran des rubriques de paie.
     */
    public function rubriqueIndemnite(int $entrepriseId): RubriquePaie
    {
        return RubriquePaie::withoutGlobalScopes()->firstOrCreate(
            ['entreprise_id' => $entrepriseId, 'code' => config('interim.rubrique_indemnite')],
            [
                'nom' => 'Indemnité d\'intérim',
                'description' => 'Différence entre le salaire minimum de la catégorie occupée et le salaire de base, '
                    .'à partir du 3e mois d\'un intérim prolongé (CCIT, art. 13).',
                'nature' => NatureRubrique::GAIN->value,
                'recurrence' => RecurrenceRubrique::VARIABLE->value,
                'mode_calcul' => ModeCalculRubrique::MONTANT_FIXE->value,
                'taux' => 0,
                'montant_defaut' => 0,
                'imposable' => true,
                'traitement_fiscal' => TraitementFiscal::IMPOSABLE->value,
                'pourcentage_imposable' => 100,
                'soumis_cotisation' => true,
                'actif' => true,
                'etat' => 1,
            ],
        );
    }
}
