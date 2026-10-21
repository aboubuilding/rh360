<?php

namespace App\Domain\Paie\Services;

use App\Domain\Carriere\Models\SituationCarriere;
use App\Domain\Carriere\Services\CalculateurAncienneteUtile;
use App\Domain\Conges\Enums\FamilleDemande;
use App\Domain\Conges\Models\DemandeConge;
use App\Domain\Conges\Models\TypeConge;
use App\Domain\Conges\Services\CalculateurAllocationConge;
use App\Domain\Conges\Services\GrandLivreConges;
use App\Domain\Conges\Services\ParametresDemandes;
use App\Domain\Paie\Enums\ModeCalculRubrique;
use App\Domain\Paie\Enums\NatureRubrique;
use App\Domain\Paie\Enums\RecurrenceRubrique;
use App\Domain\Paie\Enums\TraitementFiscal;
use App\Domain\Paie\Models\BulletinPaie;
use App\Domain\Paie\Models\ElementPaieSalarie;
use App\Domain\Paie\Models\PeriodePaie;
use App\Domain\Paie\Models\RubriquePaie;
use App\Domain\Paie\Models\SortieSalarie;
use Carbon\Carbon;

/**
 * Éléments du solde de tout compte d'un salarié qui quitte l'entreprise :
 * - indemnité compensatrice de préavis, due si l'employeur dispense le salarié de l'effectuer,
 *   non due s'il a demandé la dispense ou en cas de faute grave ou lourde (CCIT art. 19, 21 ;
 *   Code art. 77, 82, 83) : 1 mois (exécution), 3 mois (maîtrise et cadres) ;
 * - heures de recherche d'emploi non utilisées à la demande de l'employeur (CCIT art. 21) ;
 * - indemnité compensatrice des congés acquis non pris (Code art. 201, 202) ;
 * - indemnité de licenciement après un an : 35 %, 40 % puis 45 % du salaire global mensuel
 *   moyen des 12 derniers mois par année, fractions comprises (CCIT art. 25) ;
 * - rupture conventionnelle : au moins l'indemnité de licenciement (Code art. 102) ;
 * - licenciement économique : mesure d'accompagnement non imposable, au moins 2 mois de brut
 *   (exécution) ou 1 mois (maîtrise et cadres) (CCIT art. 23, plus favorable que le Code art. 95) ;
 * - fin de CDD ou de projet après 4 ans consécutifs : 1 mois du dernier brut (Code art. 54, 101) ;
 * - retraite : 45 %, 50 % ou 60 % de l'indemnité de licenciement, au moins 3 mois (CCIT art. 26) ;
 * - décès : indemnité égale à celle de licenciement pour les héritiers en ligne directe, et frais
 *   funéraires d'au moins un mois de salaire global moyen (CCIT art. 70).
 */
class CalculateurSoldeToutCompte
{
    /** @var array<string, array{0: string, 1: bool, 2: bool}> code => [libellé, imposable, soumis à cotisation] */
    public const RUBRIQUES = [
        'PREAVIS-IND' => ['Indemnité compensatrice de préavis', true, true],
        'HRE' => ['Heures de recherche d\'emploi non utilisées', true, true],
        'ICC' => ['Indemnité compensatrice de congés payés', true, true],
        'IND-LIC' => ['Indemnité de licenciement', true, false],
        'IND-RC' => ['Indemnité de rupture conventionnelle', true, false],
        'ACCOMP-ECO' => ['Mesure d\'accompagnement (licenciement économique)', false, false],
        'IND-FIN-CTR' => ['Indemnité de fin de contrat', true, false],
        'IND-RETRAITE' => ['Indemnité de départ à la retraite', true, false],
        'IND-DECES' => ['Indemnité de décès (héritiers en ligne directe)', true, false],
        'FRAIS-FUNER' => ['Participation aux frais funéraires', false, false],
    ];

    public function __construct(
        private CalculateurAnciennete $anciennete,
        private CalculateurAncienneteUtile $ancienneteUtile,
        private GrandLivreConges $grandLivre,
        private CalculateurAllocationConge $allocation,
        private ParametresDemandes $parametres,
    ) {}

    // Rubriques de sortie manquantes d'une entreprise (idempotent). Leur traitement fiscal et social
    // reste à confirmer selon les paramètres externes et se modifie dans les rubriques.
    public static function installerRubriques(int $entrepriseId): void
    {
        foreach (self::RUBRIQUES as $code => [$nom, $imposable, $cotisable]) {
            RubriquePaie::withoutGlobalScopes()->firstOrCreate(
                ['entreprise_id' => $entrepriseId, 'code' => $code],
                [
                    'nom' => $nom,
                    'nature' => NatureRubrique::GAIN->value,
                    'recurrence' => RecurrenceRubrique::PONCTUELLE->value,
                    'mode_calcul' => ModeCalculRubrique::MONTANT_FIXE->value,
                    'imposable' => $imposable,
                    'traitement_fiscal' => ($imposable ? TraitementFiscal::IMPOSABLE : TraitementFiscal::EXONERE)->value,
                    'pourcentage_imposable' => $imposable ? 100 : 0,
                    'soumis_cotisation' => $cotisable,
                    'est_frais' => false,
                    'actif' => true,
                    'etat' => 1,
                ],
            );
        }
    }

    /**
     * @return array{lignes: array<int, array{0: string, 1: string, 2: float, 3: float}>, alertes: array<int, string>, details: array<string, mixed>}
     */
    public function calculer(SortieSalarie $sortie): array
    {
        $salarie = $sortie->salarie;
        $date = $sortie->date_cessation->copy()->startOfDay();
        $this->parametres->pourEntreprise($salarie->entreprise_id);
        $lignes = [];
        $alertes = [];

        // Salaire mensuel : base, ancienneté et autres éléments fixes (hors remboursements de frais).
        $base = (float) ElementPaieSalarie::where('salarie_id', $salarie->id)->where('actif', true)
            ->whereHas('rubrique', fn ($q) => $q->where('code', 'SAL-BASE'))->value('montant');
        $baseEtAnciennete = $base + $this->anciennete->calculerPrime($salarie, $base, $date);
        $autresFixes = (float) ElementPaieSalarie::where('salarie_id', $salarie->id)->where('actif', true)->where('exclu', false)
            ->whereHas('rubrique', fn ($q) => $q->where('nature', NatureRubrique::GAIN->value)->where('est_frais', false)->whereNotIn('code', ['SAL-BASE']))
            ->sum('montant');
        $mensuel = $baseEtAnciennete + $autresFixes;
        $salaireGlobal = $this->salaireGlobalMoyen($salarie->id, $salarie->entreprise_id, $date) ?? $mensuel;

        // Ancienneté en années, fractions comprises (mois entiers).
        $depuis = $this->ancienneteUtile->dateCorrigee($salarie, $date) ?? $salarie->date_embauche ?? $date;
        $annees = max(0, (int) floor($depuis->diffInMonths($date, false))) / 12;

        $categorie = SituationCarriere::withoutGlobalScopes()->where('salarie_id', $salarie->id)->first()?->positionClassification?->categorie?->code;
        $maitriseOuCadre = $categorie && in_array(strtoupper($categorie[0]), ['M', 'C'], true);
        $sansFaute = $sortie->faute === null;

        // Préavis non effectué à la demande de l'employeur.
        if ($sortie->estLicenciement() && $sortie->preavis === 'dispense_employeur' && $sansFaute) {
            $mois = (float) ($sortie->preavis_mois ?? ($maitriseOuCadre ? 3 : 1));
            $lignes[] = ['PREAVIS-IND', "Indemnité compensatrice de préavis ({$this->nombre($mois)} mois)", $mensuel * $mois, $mois];
        }

        if ((float) $sortie->heures_recherche > 0) {
            $heures = (float) $sortie->heures_recherche;
            $lignes[] = ['HRE', "Heures de recherche d'emploi non utilisées ({$this->nombre($heures)} h)", $heures * $baseEtAnciennete / 173.33, $heures];
        }

        // Congés acquis non pris, à l'allocation journalière (au moins le salaire maintenu).
        $conge = TypeConge::pourEntreprise($salarie->entreprise_id)->where('famille', FamilleDemande::CONGE_PAYE->value)->first();
        if ($conge) {
            // Droits acquis à la cessation, y compris ceux enregistrés après elle (régularisation du solde).
            $jours = $this->grandLivre->disponible($salarie, $conge, $date->copy()->max(today())->copy(), true);
            if ($jours > 0) {
                $simulation = new DemandeConge(['entreprise_id' => $salarie->entreprise_id, 'salarie_id' => $salarie->id, 'date_debut' => $date, 'duree_jours' => $jours]);
                $simulation->setRelation('typeConge', $conge);
                $parJour = $this->allocation->calculer($simulation)['par_jour'] ?? null;
                $maintien = $mensuel / 30;
                if ($parJour === null || ($this->parametres->get('conge_comparaison_maintien') && $parJour < $maintien)) {
                    $parJour = $maintien;
                }
                $lignes[] = ['ICC', "Indemnité compensatrice de congés ({$this->nombre($jours)} jours acquis non pris)", $jours * $parJour, $jours];
            }
        }

        $licenciement = $annees >= 1 ? $this->indemniteLicenciement($salaireGlobal, $annees) : 0.0;

        switch ($sortie->motif) {
            case 'licenciement_personnel':
            case 'licenciement_economique':
                if ($sansFaute && $licenciement > 0) {
                    $lignes[] = ['IND-LIC', "Indemnité de licenciement ({$this->nombre($annees)} ans)", $licenciement, $annees];
                }
                break;
            case 'rupture_conventionnelle':
                $lignes[] = ['IND-RC', 'Indemnité de rupture conventionnelle', max((float) $sortie->montant_convenu, $licenciement), $annees];
                break;
            case 'fin_cdd':
            case 'fin_projet':
                if (! $sortie->exception_fin_contrat && $annees >= 4) {
                    $lignes[] = ['IND-FIN-CTR', 'Indemnité de fin de contrat (1 mois du dernier salaire brut)', $mensuel, 1];
                }
                break;
            case 'retraite':
                $taux = $annees < 6 ? 0.45 : ($annees <= 10 ? 0.50 : 0.60);
                $lignes[] = ['IND-RETRAITE', 'Indemnité de départ à la retraite', max($licenciement * $taux, 3 * $baseEtAnciennete), $annees];
                break;
            case 'deces':
                if ($licenciement > 0) {
                    $lignes[] = ['IND-DECES', 'Indemnité de décès (héritiers en ligne directe)', $licenciement, $annees];
                }
                break;
        }

        if ($sortie->motif === 'licenciement_economique') {
            $mois = $maitriseOuCadre ? 1 : 2;
            $lignes[] = ['ACCOMP-ECO', "Mesure d'accompagnement ({$mois} mois de salaire brut)", $mensuel * $mois, $mois];
        }
        if ($sortie->motif === 'deces') {
            $lignes[] = ['FRAIS-FUNER', 'Participation aux frais funéraires', $salaireGlobal, 1];
        }
        if ($sortie->motif === 'rupture_conventionnelle' && (float) $sortie->montant_convenu < $licenciement) {
            $alertes[] = 'Le montant convenu est inférieur à l\'indemnité de licenciement : celle-ci est retenue.';
        }
        if (in_array($sortie->motif, ['fin_cdd', 'fin_projet'], true) && $annees >= 4 && ! $sortie->exception_fin_contrat) {
            $alertes[] = 'Indemnité de fin de contrat calculée sur 4 ans d\'ancienneté : vérifiez qu\'il s\'agit de 4 années consécutives de contrats.';
        }

        return [
            'lignes' => array_map(fn ($l) => [$l[0], $l[1], round($l[2], 2), $l[3]], $lignes),
            'alertes' => $alertes,
            'details' => [
                'salaire_mensuel' => round($mensuel, 2),
                'salaire_global_moyen' => round($salaireGlobal, 2),
                'anciennete_annees' => round($annees, 4),
                'indemnite_licenciement' => round($licenciement, 2),
            ],
        ];
    }

    // 35 % par année jusqu'à 5 ans, 40 % de la 6e à la 10e, 45 % au-delà, fractions comprises.
    public function indemniteLicenciement(float $salaireGlobal, float $annees): float
    {
        $taux = 0.35 * min($annees, 5) + 0.40 * min(max($annees - 5, 0), 5) + 0.45 * max($annees - 10, 0);

        return round($salaireGlobal * $taux, 2);
    }

    // Moyenne mensuelle des bruts (hors remboursements de frais) des 12 mois précédant la sortie.
    private function salaireGlobalMoyen(int $salarieId, int $entrepriseId, Carbon $date): ?float
    {
        $debut = $date->copy()->startOfMonth()->subMonthsNoOverflow(12);
        $fin = $date->copy()->startOfMonth()->subMonthNoOverflow();
        $periodes = PeriodePaie::pourEntreprise($entrepriseId)
            ->whereRaw('(annee * 100 + mois) between ? and ?', [$debut->year * 100 + $debut->month, $fin->year * 100 + $fin->month])
            ->pluck('id');
        $bruts = BulletinPaie::where('salarie_id', $salarieId)->whereIn('periode_id', $periodes)->pluck('montant_brut');

        return $bruts->isEmpty() ? null : (float) $bruts->avg();
    }

    private function nombre(float $valeur): string
    {
        return rtrim(rtrim(number_format($valeur, 2, ',', ' '), '0'), ',');
    }
}
