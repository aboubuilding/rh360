<?php

namespace App\Domain\Interim\Services;

use App\Domain\Contrats\Enums\StatutContrat;
use App\Domain\Contrats\Enums\TypeContrat;
use App\Domain\Contrats\Models\Contrat;
use App\Domain\Contrats\Services\IndemniteFinCddCalculator;
use App\Domain\Interim\Models\MaintienRemuneration;
use App\Domain\Paie\Enums\ModeCalculRubrique;
use App\Domain\Paie\Enums\NatureRubrique;
use App\Domain\Paie\Enums\RecurrenceRubrique;
use App\Domain\Paie\Enums\TraitementFiscal;
use App\Domain\Paie\Models\ElementPaieSalarie;
use App\Domain\Paie\Models\PeriodePaie;
use App\Domain\Paie\Models\RubriquePaie;
use App\Domain\Paie\Models\SaisiePaie;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Saisies de paie des règles connexes, recalculées pour une période non figée :
 * - RG-INF-01 à 03 : complément de maintien du salaire antérieur ;
 * - RG-REM-04 : indemnité de fin de contrat payée avec le dernier salaire.
 */
class PreparateurPaieReglesConnexes
{
    public const SOURCE_MAINTIEN = 'maintien_salaire';

    public const SOURCE_FIN_CDD = 'fin_cdd';

    public function __construct(
        private IndemniteFinCddCalculator $finCdd,
        private IndemniteInterimCalculator $prorata,
        private ParametresInterim $parametres,
    ) {}

    public function preparer(PeriodePaie $periode): RapportPaieInterims
    {
        if ($periode->estFigee()) {
            throw new \DomainException('Période validée : les éléments ne peuvent plus être modifiés.');
        }

        $this->parametres->pourEntreprise($periode->entreprise_id);
        $rapport = new RapportPaieInterims;
        $debutMois = Carbon::create($periode->annee, $periode->mois, 1)->startOfDay();
        $finMois = $debutMois->copy()->endOfMonth()->startOfDay();

        DB::transaction(function () use ($periode, $rapport, $debutMois, $finMois) {
            SaisiePaie::where('periode_id', $periode->id)
                ->whereIn('source', [self::SOURCE_MAINTIEN, self::SOURCE_FIN_CDD])->delete();

            $this->maintiens($periode, $rapport, $debutMois, $finMois);
            $this->indemnitesFinCdd($periode, $rapport, $debutMois, $finMois);
        });

        return $rapport;
    }

    // RG-INF-01 à 03 — si le salaire de base a baissé, un complément rétablit le salaire maintenu.
    private function maintiens(PeriodePaie $periode, RapportPaieInterims $rapport, Carbon $debutMois, Carbon $finMois): void
    {
        $maintiens = MaintienRemuneration::withoutGlobalScopes()
            ->where('entreprise_id', $periode->entreprise_id)
            ->where('etat', 1)
            ->whereNotNull('salaire_base_maintenu')
            ->where('date_debut', '<=', $finMois)
            ->where(fn ($q) => $q->whereNull('date_fin')->orWhere('date_fin', '>=', $debutMois))
            ->get();

        foreach ($maintiens as $maintien) {
            $base = (int) ElementPaieSalarie::withoutGlobalScopes()->where('salarie_id', $maintien->salarie_id)->where('actif', true)
                ->whereHas('rubrique', fn ($q) => $q->withoutGlobalScopes()->where('code', 'SAL-BASE'))->value('montant');
            $ecart = $maintien->salaire_base_maintenu - $base;
            if ($ecart <= 0) {
                continue;
            }

            $du = $maintien->date_debut->copy()->max($debutMois);
            $au = ($maintien->date_fin ?? $finMois)->copy()->min($finMois);
            [$jours, $joursBase] = $this->prorata->joursProrata($du, $au, $debutMois, $finMois);
            $montant = (int) round($ecart * $jours / $joursBase);

            $this->saisir($periode, $rapport, $maintien->salarie_id, $this->rubrique($periode->entreprise_id, config('interim.rubrique_maintien_salaire'),
                'Maintien de salaire', 'Complément rétablissant le salaire maintenu (affectation à un emploi inférieur ou mutation pour grossesse).'),
                $montant, self::SOURCE_MAINTIEN,
                "{$maintien->motif->libelle()} : salaire maintenu ".number_format($maintien->salaire_base_maintenu, 0, ',', ' ')
                .' − base '.number_format($base, 0, ',', ' ')." ({$jours}/{$joursBase} j)");
        }
    }

    // RG-REM-04 — l'indemnité est payée avec le dernier salaire, dans la période où le CDD prend fin.
    private function indemnitesFinCdd(PeriodePaie $periode, RapportPaieInterims $rapport, Carbon $debutMois, Carbon $finMois): void
    {
        $contrats = Contrat::withoutGlobalScopes()
            ->where('entreprise_id', $periode->entreprise_id)
            ->where('type_contrat', TypeContrat::CDD->value)
            ->where('statut', StatutContrat::SIGNE->value)
            ->whereBetween('date_fin', [$debutMois, $finMois])
            ->get();

        foreach ($contrats as $contrat) {
            $evaluation = $this->finCdd->evaluer($contrat);
            if (! $evaluation['due']) {
                continue;
            }

            $brut = $this->finCdd->dernierSalaireBrut($contrat, $periode);
            if ($brut === null) {
                $rapport->anomalie($contrat->salarie_id, $contrat->id, "Indemnité de fin de contrat due (contrat {$contrat->reference}) mais aucun "
                    .'bulletin antérieur ne donne le dernier salaire brut : à saisir manuellement.');

                continue;
            }

            $this->saisir($periode, $rapport, $contrat->salarie_id, $this->rubrique($periode->entreprise_id, config('interim.rubrique_indemnite_fin_cdd'),
                'Indemnité de fin de contrat', 'Un mois du dernier salaire brut après 4 ans consécutifs de CDD sans CDI.'),
                $brut, self::SOURCE_FIN_CDD,
                "Contrat {$contrat->reference} : {$evaluation['raison']} Un mois du dernier salaire brut");
        }
    }

    private function saisir(PeriodePaie $periode, RapportPaieInterims $rapport, int $salarieId, RubriquePaie $rubrique, int $montant, string $source, string $observations): void
    {
        if ($montant <= 0) {
            return;
        }

        if (SaisiePaie::where('periode_id', $periode->id)->where('salarie_id', $salarieId)->where('rubrique_id', $rubrique->id)->exists()) {
            $rapport->anomalie($salarieId, 0, "Une saisie manuelle existe déjà sur la rubrique « {$rubrique->nom} » : "
                .number_format($montant, 0, ',', ' ').' FCFA non ajoutés.');

            return;
        }

        (new SaisiePaie)->forceFill([
            'entreprise_id' => $periode->entreprise_id,
            'periode_id' => $periode->id,
            'salarie_id' => $salarieId,
            'rubrique_id' => $rubrique->id,
            'quantite' => 1,
            'taux' => 0,
            'montant' => $montant,
            'observations' => $observations,
            'source' => $source,
            'cree_par' => auth()->id(),
            'etat' => 1,
        ])->save();

        $rapport->saisies[] = ['salarie_id' => $salarieId, 'interim_id' => 0, 'rubrique_id' => $rubrique->id,
            'rubrique_nom' => $rubrique->nom, 'montant' => $montant, 'observations' => $observations];
    }

    // Rubrique créée au premier besoin ; son traitement fiscal et social se règle dans l'écran des rubriques.
    private function rubrique(int $entrepriseId, string $code, string $nom, string $description): RubriquePaie
    {
        return RubriquePaie::withoutGlobalScopes()->firstOrCreate(
            ['entreprise_id' => $entrepriseId, 'code' => $code],
            [
                'nom' => $nom,
                'description' => $description,
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
