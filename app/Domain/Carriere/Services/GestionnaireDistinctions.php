<?php

namespace App\Domain\Carriere\Services;

use App\Domain\Carriere\Models\DistinctionHonorifique;
use App\Domain\Carriere\Models\SituationCarriere;
use App\Domain\Paie\Enums\ModeCalculRubrique;
use App\Domain\Paie\Enums\NatureRubrique;
use App\Domain\Paie\Enums\RecurrenceRubrique;
use App\Domain\Paie\Enums\TraitementFiscal;
use App\Domain\Paie\Models\ElementPaieSalarie;
use App\Domain\Paie\Models\PeriodePaie;
use App\Domain\Paie\Models\RubriquePaie;
use App\Domain\Paie\Models\SaisiePaie;
use App\Domain\Paie\Services\CalculateurAnciennete;
use App\Domain\Personnel\Models\Salarie;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Distinctions honorifiques (CCIT art. 64) : diplôme et gratification en mois de
 * (salaire de base + prime d'ancienneté) à 10, 15, 20, 25, 30 et 35 ans de service.
 */
class GestionnaireDistinctions
{
    public const RUBRIQUE = 'GRATIF-DISTINCTION';

    /** Palier (années) → nombre de mois ; 35 ans : au moins 4 mois (paramètre). */
    private const PALIERS = [10 => 1.0, 15 => 1.5, 20 => 2.0, 25 => 2.5, 30 => 3.0, 35 => null];

    public function __construct(
        private CalculateurAncienneteUtile $anciennete,
        private CalculateurAnciennete $prime,
        private ParametresAvancement $parametres,
    ) {}

    /**
     * Crée les distinctions dues (paliers atteints à la date) qui n'existent pas encore.
     */
    public function generer(int $entrepriseId, ?Carbon $date = null): int
    {
        $date ??= today();
        $this->parametres->pourEntreprise($entrepriseId);
        $creees = 0;

        $salaries = Salarie::withoutGlobalScopes()->where('entreprise_id', $entrepriseId)->where('actif', true)->get();
        foreach ($salaries as $salarie) {
            $debut = $this->debutAnciennete($salarie, $date);
            if (! $debut) {
                continue;
            }

            foreach (array_keys(self::PALIERS) as $palier) {
                $anniversaire = $debut->copy()->addYears($palier);
                if ($anniversaire->gt($date)
                    || DistinctionHonorifique::withoutGlobalScopes()->where('salarie_id', $salarie->id)->where('palier_annees', $palier)->exists()) {
                    continue;
                }

                $calcul = $this->calculer($salarie, $palier, $anniversaire);
                DistinctionHonorifique::create(['entreprise_id' => $entrepriseId, 'salarie_id' => $salarie->id,
                    'palier_annees' => $palier, 'date_anniversaire' => $anniversaire, 'statut' => 'due'] + $calcul);
                $creees++;
            }
        }

        return $creees;
    }

    /**
     * @return array{mois: float, base: int, prime_anciennete: int, montant: int}
     */
    public function calculer(Salarie $salarie, int $palier, Carbon $date): array
    {
        $this->parametres->pourEntreprise($salarie->entreprise_id);
        $mois = self::PALIERS[$palier] ?? (float) $this->parametres->get('distinction_35_ans_mois');

        $salaireReel = (int) ElementPaieSalarie::withoutGlobalScopes()->where('salarie_id', $salarie->id)->where('actif', true)
            ->whereHas('rubrique', fn ($q) => $q->withoutGlobalScopes()->where('code', 'SAL-BASE'))->value('montant');

        $base = $salaireReel;
        if ($this->parametres->get('distinction_base') === 'minimum_categorie') {
            $position = SituationCarriere::withoutGlobalScopes()->where('salarie_id', $salarie->id)->first()?->positionClassification;
            $base = (int) ($position ? ($this->prime->minimumCategorie($position) ?? $salaireReel) : $salaireReel);
        }

        $primeAnciennete = (int) $this->prime->calculerPrime($salarie, $salaireReel, $date->copy()->endOfMonth());
        $pas = max(1, (int) $this->parametres->get('arrondi_montants'));

        return [
            'mois' => $mois,
            'base' => $base,
            'prime_anciennete' => $primeAnciennete,
            'montant' => (int) (round($mois * ($base + $primeAnciennete) / $pas) * $pas),
        ];
    }

    /**
     * Porte la gratification en paie, dans la période ouverte, comme saisie de la rubrique dédiée.
     */
    public function verser(DistinctionHonorifique $distinction, PeriodePaie $periode): DistinctionHonorifique
    {
        if ($distinction->statut === 'versee') {
            throw new \DomainException('Cette gratification a déjà été versée.');
        }
        if ($periode->estFigee()) {
            throw new \DomainException('La période de paie est validée : choisir une période ouverte.');
        }

        return DB::transaction(function () use ($distinction, $periode) {
            $rubrique = RubriquePaie::withoutGlobalScopes()->firstOrCreate(
                ['entreprise_id' => $distinction->entreprise_id, 'code' => self::RUBRIQUE],
                [
                    'nom' => 'Gratification de distinction honorifique',
                    'description' => 'Gratification en mois de salaire de base et de prime d\'ancienneté, selon l\'ancienneté de service.',
                    'nature' => NatureRubrique::GAIN->value,
                    'recurrence' => RecurrenceRubrique::PONCTUELLE->value,
                    'mode_calcul' => ModeCalculRubrique::MONTANT_FIXE->value,
                    'taux' => 0, 'montant_defaut' => 0,
                    'imposable' => true, 'traitement_fiscal' => TraitementFiscal::IMPOSABLE->value,
                    'pourcentage_imposable' => 100, 'soumis_cotisation' => true, 'actif' => true, 'etat' => 1,
                ],
            );

            (new SaisiePaie)->forceFill([
                'entreprise_id' => $distinction->entreprise_id,
                'periode_id' => $periode->id,
                'salarie_id' => $distinction->salarie_id,
                'rubrique_id' => $rubrique->id,
                'quantite' => 1, 'taux' => 0,
                'montant' => $distinction->montant,
                'observations' => "Distinction honorifique — {$distinction->palier_annees} ans de service : "
                    .rtrim(rtrim(number_format($distinction->mois, 1, ',', ' '), '0'), ',').' mois',
                'source' => 'distinction',
                'cree_par' => auth()->id(),
                'etat' => 1,
            ])->save();

            $distinction->update(['statut' => 'versee', 'periode_paie_id' => $periode->id, 'decide_par' => auth()->id()]);

            return $distinction->fresh();
        });
    }

    // Début de l'ancienneté retenue : ancienneté utile (corrigée) ou brute depuis l'embauche.
    private function debutAnciennete(Salarie $salarie, Carbon $date): ?Carbon
    {
        if (! $salarie->date_embauche) {
            return null;
        }

        return $this->parametres->get('distinction_anciennete') === 'brute'
            ? Carbon::parse($salarie->date_embauche)->startOfDay()
            : $this->anciennete->dateCorrigee($salarie, $date);
    }
}
