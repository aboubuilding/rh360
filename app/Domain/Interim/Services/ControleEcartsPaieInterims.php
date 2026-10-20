<?php

namespace App\Domain\Interim\Services;

use App\Domain\Interim\Models\InterimInterne;
use App\Domain\Paie\Enums\StatutPeriode;
use App\Domain\Paie\Models\BulletinPaie;
use App\Domain\Paie\Models\PeriodePaie;
use App\Domain\Personnel\Models\Salarie;

/**
 * Écarts entre l'indemnité d'intérim due (RG-INT-06, RG-INT-10) et l'indemnité versée sur les
 * périodes de paie validées. Lecture seule : aucune écriture de paie ni comptable. Le salaire
 * de base retenu est celui du bulletin du mois.
 */
class ControleEcartsPaieInterims
{
    public function __construct(
        private IndemniteInterimCalculator $calculateur,
        private ParametresInterim $parametres,
    ) {}

    /**
     * @return list<array{periode:string, matricule:?string, salarie:?string, du:int, verse:int, ecart:int, nature:string, detail:string}>
     */
    public function controler(?int $entrepriseId = null): array
    {
        $lignes = [];
        $code = config('interim.rubrique_indemnite');

        $periodes = PeriodePaie::withoutGlobalScopes()
            ->where('statut', StatutPeriode::VALIDEE->value)
            ->when($entrepriseId, fn ($q) => $q->where('entreprise_id', $entrepriseId))
            ->orderBy('annee')->orderBy('mois')
            ->get();

        foreach ($periodes as $periode) {
            $this->parametres->pourEntreprise($periode->entreprise_id);
            $libelle = sprintf('%04d-%02d', $periode->annee, $periode->mois);

            $bulletins = BulletinPaie::withoutGlobalScopes()->where('periode_id', $periode->id)
                ->with('lignes')->get()->keyBy('salarie_id');

            $dus = [];
            $details = [];
            $interims = InterimInterne::withoutGlobalScopes()
                ->where('entreprise_id', $periode->entreprise_id)
                ->where('etat', 1)
                ->with('categorieOccupee')
                ->get();

            foreach ($interims as $interim) {
                $bulletin = $bulletins->get($interim->salarie_id);
                $base = $bulletin ? (int) round((float) $bulletin->lignes->firstWhere('code', 'SAL-BASE')?->montant) : null;

                try {
                    $indemnite = $this->calculateur->pourMois($interim, $periode->annee, $periode->mois, $base);
                } catch (IndemniteIncalculable $e) {
                    $details[$interim->salarie_id][] = "Intérim #{$interim->id} : {$e->getMessage()}";

                    continue;
                }

                if ($indemnite) {
                    $dus[$interim->salarie_id] = ($dus[$interim->salarie_id] ?? 0) + $indemnite->montant;
                    $details[$interim->salarie_id][] = "Intérim #{$interim->id} : {$indemnite->mensuel} FCFA/mois, "
                        ."{$indemnite->jours}/{$indemnite->joursBase} j du {$indemnite->du->format('d/m/Y')} au {$indemnite->au->format('d/m/Y')}";
                }
            }

            $verses = $bulletins->map(fn ($b) => (int) round((float) $b->lignes->where('code', $code)->sum('montant')))
                ->filter(fn ($montant) => $montant !== 0)
                ->all();

            foreach (array_unique(array_merge(array_keys($dus), array_keys($verses), array_keys($details))) as $salarieId) {
                $du = $dus[$salarieId] ?? 0;
                $verse = $verses[$salarieId] ?? 0;
                $ecart = $du - $verse;
                $salarie = Salarie::withoutGlobalScopes()->find($salarieId);

                $lignes[] = [
                    'periode' => $libelle,
                    'matricule' => $salarie?->matricule,
                    'salarie' => $salarie?->nom_complet,
                    'du' => $du,
                    'verse' => $verse,
                    'ecart' => $ecart,
                    'nature' => match (true) {
                        $ecart > 0 => 'rappel',
                        $ecart < 0 => 'trop-perçu',
                        default => 'conforme',
                    },
                    'detail' => implode(' | ', $details[$salarieId] ?? ['Indemnité versée sans intérim interne enregistré']),
                ];
            }
        }

        return $lignes;
    }
}
