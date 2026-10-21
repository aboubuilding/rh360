<?php

namespace App\Domain\Conges\Services;

use App\Domain\Conges\Enums\FamilleDemande;
use App\Domain\Conges\Enums\ImpactCongeAnnuel;
use App\Domain\Conges\Enums\StatutDemandeConge;
use App\Domain\Conges\Enums\UniteConge;
use App\Domain\Conges\Models\Absence;
use App\Domain\Conges\Models\DemandeConge;
use App\Domain\Conges\Models\MouvementConge;
use App\Domain\Conges\Models\SoldeConge;
use App\Domain\Conges\Models\TypeConge;
use App\Domain\Personnel\Models\Salarie;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Grand livre des congés payés, tenu en mouvements datés par lot annuel (année civile
 * d'acquisition).
 *
 * - Acquisition : droit annuel / 12 par mois de service effectif (2,5 jours), réduite des jours
 *   non assimilés (Code art. 200 ; CCIT art. 49 a). Les absences assimilées ne la réduisent pas.
 * - Ouverture d'un lot : le 1er janvier suivant, et pas avant 12 mois de service ; un lot repris
 *   d'un solde existant est ouvert dès sa reprise. Par anticipation, et d'accord parties, le lot
 *   en cours peut être pris au prorata après 6 mois de service (Code art. 201).
 * - Report : la durée de service ouvrant droit au congé ne peut excéder 2 ans (Code art. 201) ;
 *   le reliquat d'un lot expire à l'ouverture du lot qui le suit de deux ans, ou est signalé
 *   pour un départ imposé, selon le paramétrage.
 * - Prise : les jours sont imputés au lot le plus ancien d'abord.
 *
 * Les lignes soldes_conges (une par lot) sont la projection de ce grand livre.
 */
class GrandLivreConges
{
    // Demandes qui comptent dans le décompte : réservées ou consommées.
    private const STATUTS_EFFECTIFS = [
        StatutDemandeConge::AUTORISEE, StatutDemandeConge::PROGRAMMEE,
        StatutDemandeConge::EN_COURS, StatutDemandeConge::REPRISE_CONFIRMEE,
    ];

    /** @var array<string, bool> reprises en cours (salarié-type), contre la réentrance */
    private array $enReprise = [];

    public function __construct(private ParametresDemandes $parametres) {}

    public static function gere(?TypeConge $type): bool
    {
        return $type?->famille === FamilleDemande::CONGE_PAYE;
    }

    // --- Lecture ---

    /**
     * Situation des lots à une date.
     *
     * @return Collection<int, array{lot: int, reste: float, acquis: float, pris: float, ouvert_le: ?Carbon, expire_le: Carbon, ouvert: bool, expire: bool}>
     */
    public function lots(Salarie $salarie, TypeConge $type, ?Carbon $date = null): Collection
    {
        $date = ($date ?? today())->copy()->startOfDay();
        $this->parametres->pourEntreprise($salarie->entreprise_id);

        return $this->mouvements($salarie, $type)
            ->groupBy('lot')
            ->map(function (Collection $mouvements, int $lot) use ($salarie, $date) {
                $ouvertLe = $this->dateOuverture($salarie, $lot, $mouvements);
                $expireLe = $this->dateExpiration($lot);
                $valeurs = $mouvements->filter(fn ($m) => $m->date_valeur->lte($date) || $m->nature === MouvementConge::PRISE);

                return [
                    'lot' => $lot,
                    'reste' => round((float) $valeurs->sum('jours'), 2),
                    'acquis' => round((float) $valeurs->whereIn('nature', [MouvementConge::ACQUISITION, MouvementConge::OUVERTURE, MouvementConge::ARRONDI])->sum('jours'), 2),
                    'pris' => round(-1 * (float) $valeurs->whereIn('nature', [MouvementConge::PRISE, MouvementConge::RECREDIT])->sum('jours'), 2),
                    'ouvert_le' => $ouvertLe,
                    'expire_le' => $expireLe,
                    'ouvert' => $ouvertLe !== null && $ouvertLe->lte($date),
                    'expire' => $expireLe->lte($date),
                ];
            })
            ->sortKeys()
            ->values();
    }

    /**
     * Jours disponibles à une date : lots ouverts et non expirés ; avec l'anticipation accordée,
     * le lot en cours au prorata de ce qui est déjà acquis.
     */
    public function disponible(Salarie $salarie, TypeConge $type, ?Carbon $date = null, bool $anticipation = false): float
    {
        $this->assurerReprise($salarie, $type);

        return round((float) $this->lotsImputables($salarie, $type, $date ?? today(), $anticipation)->sum('reste'), 2);
    }

    // --- Écriture ---

    /**
     * Reprise des soldes existants, une seule fois par salarié et par type : chaque année de
     * solde devient un lot ouvert, crédité de ses droits (acquis et ajustements, hors report),
     * puis les demandes en cours sont imputées.
     */
    public function assurerReprise(Salarie $salarie, TypeConge $type): void
    {
        $cle = "{$salarie->id}-{$type->id}";
        if (isset($this->enReprise[$cle])) {
            return;
        }
        if (MouvementConge::pourEntreprise($salarie->entreprise_id)->where('salarie_id', $salarie->id)->where('type_conge_id', $type->id)->exists()) {
            return;
        }

        $this->enReprise[$cle] = true;
        try {
            DB::transaction(fn () => $this->reprendre($salarie, $type));
        } finally {
            unset($this->enReprise[$cle]);
        }
    }

    private function reprendre(Salarie $salarie, TypeConge $type): void
    {
        $soldes = SoldeConge::pourEntreprise($salarie->entreprise_id)
            ->where('salarie_id', $salarie->id)->where('type_conge_id', $type->id)->orderBy('annee')->get();

        foreach ($soldes as $solde) {
            $droits = round((float) $solde->acquis + (float) $solde->ajustement, 2);
            if ($droits == 0.0) {
                continue;
            }
            // Comme dans l'ancien décompte, les droits d'une année repris sont utilisables dès son 1er janvier.
            $this->ecrire($salarie, $type, $solde->annee, MouvementConge::OUVERTURE, $droits, Carbon::create($solde->annee, 1, 1), null, null, "Reprise du solde {$solde->annee}");
        }

        $demandes = DemandeConge::pourEntreprise($salarie->entreprise_id)
            ->where('salarie_id', $salarie->id)->where('type_conge_id', $type->id)
            ->whereIn('statut', array_map(fn ($s) => $s->value, self::STATUTS_EFFECTIFS))
            ->orderBy('date_debut')->get();
        foreach ($demandes as $demande) {
            $this->imputerPrise($demande, forcer: true);
        }
    }

    /**
     * Acquiert les mois échus jusqu'à la fin du mois donné (idempotent). Les mois couverts par
     * un solde repris ne sont pas acquis une seconde fois.
     *
     * @return float jours acquis par cet appel
     */
    public function acquerirJusqua(Salarie $salarie, TypeConge $type, Carbon $finMois): float
    {
        $this->assurerReprise($salarie, $type);
        $debut = $this->premierMoisAcquis($salarie, $type);
        if (! $debut) {
            return 0.0;
        }

        $acquis = 0.0;
        for ($mois = $debut->copy(); $mois->lte($finMois); $mois->addMonthNoOverflow()) {
            $acquis += $this->acquerirMois($salarie, $type, $mois->copy());
        }

        return round($acquis, 2);
    }

    // Acquisition d'un mois civil, réduite des jours non assimilés à du service effectif.
    public function acquerirMois(Salarie $salarie, TypeConge $type, Carbon $mois): float
    {
        $mois = $mois->copy()->startOfMonth();
        $existe = MouvementConge::pourEntreprise($salarie->entreprise_id)
            ->where('salarie_id', $salarie->id)->where('type_conge_id', $type->id)
            ->where('nature', MouvementConge::ACQUISITION)->where('mois_reference', $mois->toDateString())->exists();
        if ($existe) {
            return 0.0;
        }

        $fin = $mois->copy()->endOfMonth()->startOfDay();
        $joursMois = $mois->daysInMonth;
        $nonAssimiles = $this->joursNonAssimiles($salarie, $mois, $fin);
        $jours = round(((float) $type->droit_annuel / 12) * ($joursMois - $nonAssimiles) / $joursMois, 2);

        $this->ecrire(
            $salarie, $type, $mois->year, MouvementConge::ACQUISITION, $jours, $fin, $mois, null,
            $nonAssimiles > 0 ? "{$nonAssimiles} jour(s) non assimilé(s) à du service effectif" : null,
        );

        return $jours;
    }

    /**
     * Impute une prise de congé au lot le plus ancien d'abord (idempotent). Sans « forcer »,
     * un solde insuffisant est refusé ; avec, le reliquat est porté en négatif sur le dernier lot.
     */
    public function imputerPrise(DemandeConge $demande, bool $anticipation = false, bool $forcer = false): void
    {
        $type = $demande->typeConge;
        $salarie = $demande->salarie;
        $this->assurerReprise($salarie, $type);
        if ($this->netPris($demande) > 0) {
            return;
        }

        $restant = round((float) $demande->duree_jours, 2);
        if ($restant <= 0) {
            return;
        }

        $lots = $this->lotsImputables($salarie, $type, $demande->date_debut, $anticipation || $demande->anticipation)
            ->filter(fn ($l) => $l['reste'] > 0);

        if (! $forcer && $lots->sum('reste') < $restant) {
            throw new \DomainException(sprintf('Solde insuffisant. Disponible : %.2f jours, demandé : %.2f jours.', $lots->sum('reste'), $restant));
        }

        foreach ($lots as $lot) {
            $part = min($restant, $lot['reste']);
            $this->ecrire($salarie, $type, $lot['lot'], MouvementConge::PRISE, -1 * $part, $demande->date_debut, null, $demande, "Demande {$demande->numero_demande}");
            $restant = round($restant - $part, 2);
            if ($restant <= 0) {
                return;
            }
        }

        // Reprise d'une demande déjà accordée : le dépassement est conservé pour être régularisé.
        $dernierLot = $this->mouvements($salarie, $type)->max('lot') ?? $demande->date_debut->year;
        $this->ecrire($salarie, $type, $dernierLot, MouvementConge::PRISE, -1 * $restant, $demande->date_debut, null, $demande, "Demande {$demande->numero_demande} (dépassement à régulariser)");
    }

    // Recrédite les jours imputés à une demande annulée, refusée ou requalifiée (idempotent).
    public function recrediter(DemandeConge $demande, string $motif = 'Demande annulée'): void
    {
        $parLot = MouvementConge::pourEntreprise($demande->entreprise_id)
            ->where('demande_id', $demande->id)->whereIn('nature', [MouvementConge::PRISE, MouvementConge::RECREDIT])
            ->get()->groupBy('lot');

        foreach ($parLot as $lot => $mouvements) {
            $net = round(-1 * (float) $mouvements->sum('jours'), 2);
            if ($net > 0) {
                $this->ecrire($demande->salarie, $demande->typeConge, (int) $lot, MouvementConge::RECREDIT, $net, today(), null, $demande, "{$motif} : {$demande->numero_demande}");
            }
        }
    }

    public function ajuster(Salarie $salarie, TypeConge $type, int $lot, float $jours, string $motif): void
    {
        $this->assurerReprise($salarie, $type);
        $this->ecrire($salarie, $type, $lot, MouvementConge::AJUSTEMENT, $jours, today(), null, null, $motif);
        $this->projeter($salarie, $type);
    }

    /**
     * Traitements à l'ouverture des lots (idempotents) : arrondi des lots qui s'ouvrent, puis
     * expiration des reliquats au-delà de la durée de report, ou signalement pour départ imposé.
     *
     * @return array{arrondis: int, expirations: int, a_imposer: array<int, array{lot: int, reste: float, expire_le: Carbon}>}
     */
    public function cloturerLots(Salarie $salarie, TypeConge $type, ?Carbon $date = null): array
    {
        $date = ($date ?? today())->copy()->startOfDay();
        $this->parametres->pourEntreprise($salarie->entreprise_id);
        $resultat = ['arrondis' => 0, 'expirations' => 0, 'a_imposer' => []];

        foreach ($this->lots($salarie, $type, $date) as $lot) {
            $mouvements = $this->mouvements($salarie, $type)->where('lot', $lot['lot']);

            if ($lot['ouvert'] && $this->parametres->get('conge_arrondi_acquisition') === 'demi_jour_superieur'
                && $mouvements->where('nature', MouvementConge::ARRONDI)->isEmpty()
                && $mouvements->where('nature', MouvementConge::OUVERTURE)->isEmpty()) {
                $acquis = (float) $mouvements->where('nature', MouvementConge::ACQUISITION)->sum('jours');
                $ecart = round(ceil($acquis * 2) / 2 - $acquis, 2);
                if ($ecart > 0) {
                    $this->ecrire($salarie, $type, $lot['lot'], MouvementConge::ARRONDI, $ecart, $lot['ouvert_le'], null, null, 'Arrondi à la demi-journée supérieure');
                    $resultat['arrondis']++;
                }
            }

            $reste = round((float) $this->mouvements($salarie, $type)->where('lot', $lot['lot'])->sum('jours'), 2);
            if ($lot['expire'] && $reste > 0) {
                if ($this->parametres->get('conge_lot_expire') === 'perte') {
                    $this->ecrire($salarie, $type, $lot['lot'], MouvementConge::EXPIRATION, -1 * $reste, $lot['expire_le'], null, null, 'Durée de report dépassée');
                    $resultat['expirations']++;
                } else {
                    $resultat['a_imposer'][] = ['lot' => $lot['lot'], 'reste' => $reste, 'expire_le' => $lot['expire_le']];
                }
            }
        }

        $this->projeter($salarie, $type);

        return $resultat;
    }

    /**
     * Met à jour la projection soldes_conges : une ligne par lot. Les jours imputés aux demandes
     * dont la reprise est confirmée sont consommés, les autres réservés.
     */
    public function projeter(Salarie $salarie, TypeConge $type): void
    {
        $statuts = DemandeConge::pourEntreprise($salarie->entreprise_id)
            ->where('salarie_id', $salarie->id)->where('type_conge_id', $type->id)
            ->pluck('statut', 'id');

        foreach ($this->mouvements($salarie, $type)->groupBy('lot') as $lot => $mouvements) {
            $pris = $mouvements->whereIn('nature', [MouvementConge::PRISE, MouvementConge::RECREDIT]);
            $consomme = 0.0;
            $reserve = 0.0;
            foreach ($pris->groupBy('demande_id') as $demandeId => $lignes) {
                $net = -1 * (float) $lignes->sum('jours');
                if (($statuts[$demandeId] ?? null) === StatutDemandeConge::REPRISE_CONFIRMEE) {
                    $consomme += $net;
                } else {
                    $reserve += $net;
                }
            }

            SoldeConge::withoutGlobalScopes()->updateOrCreate(
                ['entreprise_id' => $salarie->entreprise_id, 'salarie_id' => $salarie->id, 'type_conge_id' => $type->id, 'annee' => (int) $lot],
                [
                    'solde_ouverture' => 0,
                    'acquis' => round((float) $mouvements->whereIn('nature', [MouvementConge::ACQUISITION, MouvementConge::OUVERTURE, MouvementConge::ARRONDI])->sum('jours'), 2),
                    'ajustement' => round((float) $mouvements->whereIn('nature', [MouvementConge::AJUSTEMENT, MouvementConge::EXPIRATION])->sum('jours'), 2),
                    'consomme' => round($consomme, 2),
                    'reserve' => round($reserve, 2),
                    'etat' => 1,
                ],
            );
        }
    }

    // --- Calculs internes ---

    /**
     * Jours d'un intervalle qui ne comptent pas comme service effectif pour l'acquisition :
     * avant l'embauche ; absences dont le type suspend l'acquisition (absence non justifiée,
     * commission syndicale…) ; maladie au-delà de la durée assimilée ; selon le paramétrage, le
     * congé payé lui-même et le congé sans solde.
     */
    public function joursNonAssimiles(Salarie $salarie, Carbon $du, Carbon $au): int
    {
        $this->parametres->pourEntreprise($salarie->entreprise_id);
        $jours = [];
        $marquer = function (Carbon $debut, Carbon $fin) use (&$jours, $du, $au) {
            $debut = $debut->copy()->max($du)->copy()->startOfDay();
            $fin = $fin->copy()->min($au)->copy()->startOfDay();
            if ($debut->gt($fin)) {
                return;
            }
            foreach (CarbonPeriod::create($debut, $fin) as $jour) {
                $jours[$jour->toDateString()] = true;
            }
        };

        if ($salarie->date_embauche && $salarie->date_embauche->gt($du)) {
            $marquer($du, $salarie->date_embauche->copy()->subDay());
        }

        $demandes = DemandeConge::pourEntreprise($salarie->entreprise_id)->with('typeConge')
            ->where('salarie_id', $salarie->id)
            ->whereIn('statut', array_map(fn ($s) => $s->value, self::STATUTS_EFFECTIFS))
            ->where('date_debut', '<=', $au->toDateString())
            ->where('date_reprise', '>', $du->toDateString())
            ->get();
        foreach ($demandes as $demande) {
            $periode = $this->periodeNonAssimilee($demande);
            if ($periode) {
                $marquer(...$periode);
            }
        }

        // Absences constatées hors demande (une absence régularisée suit sa demande).
        $absences = Absence::pourEntreprise($salarie->entreprise_id)->with('typeConge')
            ->where('salarie_id', $salarie->id)
            ->whereNotIn('id', DemandeConge::pourEntreprise($salarie->entreprise_id)->whereNotNull('absence_id')->select('absence_id'))
            ->where('debut_le', '<=', $au->copy()->endOfDay())
            ->where(fn ($q) => $q->whereNull('fin_le')->orWhere('fin_le', '>=', $du))
            ->get();
        foreach ($absences as $absence) {
            if ($absence->typeConge?->impact_conge_annuel === ImpactCongeAnnuel::SUSPEND) {
                $marquer($absence->debut_le, $absence->fin_le ?? $au);
            }
        }

        return count($jours);
    }

    /** @return array{0: Carbon, 1: Carbon}|null période d'une demande non assimilée à du service effectif */
    private function periodeNonAssimilee(DemandeConge $demande): ?array
    {
        $type = $demande->typeConge;
        if (! $type || $type->unite === UniteConge::HEURE) {
            return null;
        }
        $debut = $demande->date_debut->copy();
        $fin = $demande->date_reprise->copy()->subDay();

        return match (true) {
            $type->famille === FamilleDemande::CONGE_PAYE => $this->parametres->get('conge_acquisition_pendant_conge') ? null : [$debut, $fin],
            $type->famille === FamilleDemande::SANS_SOLDE => $this->parametres->get('css_compte_acquisition') ? null : [$debut, $fin],
            // Maladie assimilée dans la limite de sa durée ; au-delà, elle ne compte plus.
            $type->famille === FamilleDemande::MALADIE => ($limite = $debut->copy()->addMonthsNoOverflow((int) $this->parametres->get('maladie_duree_max_mois')))->lte($fin)
                ? [$limite, $fin] : null,
            $type->impact_conge_annuel === ImpactCongeAnnuel::SUSPEND => [$debut, $fin],
            default => null,
        };
    }

    /** @return Collection<int, array> lots utilisables à une date, du plus ancien au plus récent */
    private function lotsImputables(Salarie $salarie, TypeConge $type, Carbon $date, bool $anticipation): Collection
    {
        return $this->lots($salarie, $type, $date)
            ->filter(fn ($l) => ! $l['expire'] && ($l['ouvert'] || ($anticipation && $l['lot'] <= $date->year)))
            ->values();
    }

    // Ouverture d'un lot : 1er janvier suivant, et pas avant le droit ouvert par le service ;
    // un lot repris d'un solde existant est ouvert dès sa reprise.
    private function dateOuverture(Salarie $salarie, int $lot, Collection $mouvements): ?Carbon
    {
        $reprise = $mouvements->firstWhere('nature', MouvementConge::OUVERTURE);
        if ($reprise) {
            return $reprise->date_valeur->copy();
        }

        $ouverture = Carbon::create($lot + 1, 1, 1)->startOfDay();
        if ($salarie->date_embauche) {
            $droit = $salarie->date_embauche->copy()->addMonthsNoOverflow((int) $this->parametres->get('conge_anciennete_droit_mois'));
            $ouverture = $ouverture->max($droit)->copy();
        }

        return $ouverture;
    }

    // Expiration : à l'ouverture du lot qui suit celui-ci de la durée de report (lot 2024 → 1er janvier 2027).
    private function dateExpiration(int $lot): Carbon
    {
        return Carbon::create($lot + 1 + (int) $this->parametres->get('conge_report_max_annees'), 1, 1)->startOfDay();
    }

    private function premierMoisAcquis(Salarie $salarie, TypeConge $type): ?Carbon
    {
        $dernierRepris = $this->mouvements($salarie, $type)->where('nature', MouvementConge::OUVERTURE)->max('lot');
        if ($dernierRepris) {
            return Carbon::create($dernierRepris + 1, 1, 1)->startOfDay();
        }

        return $salarie->date_embauche?->copy()->startOfMonth();
    }

    private function netPris(DemandeConge $demande): float
    {
        return round(-1 * (float) MouvementConge::pourEntreprise($demande->entreprise_id)
            ->where('demande_id', $demande->id)->whereIn('nature', [MouvementConge::PRISE, MouvementConge::RECREDIT])->sum('jours'), 2);
    }

    /** @return Collection<int, MouvementConge> */
    private function mouvements(Salarie $salarie, TypeConge $type): Collection
    {
        return MouvementConge::pourEntreprise($salarie->entreprise_id)
            ->where('salarie_id', $salarie->id)->where('type_conge_id', $type->id)
            ->orderBy('date_valeur')->orderBy('id')->get();
    }

    private function ecrire(Salarie $salarie, TypeConge $type, int $lot, string $nature, float $jours, Carbon $date, ?Carbon $mois, ?DemandeConge $demande, ?string $commentaire): MouvementConge
    {
        return MouvementConge::create([
            'entreprise_id' => $salarie->entreprise_id,
            'salarie_id' => $salarie->id,
            'type_conge_id' => $type->id,
            'lot' => $lot,
            'nature' => $nature,
            'jours' => round($jours, 2),
            'date_valeur' => $date->toDateString(),
            'mois_reference' => $mois?->toDateString(),
            'demande_id' => $demande?->id,
            'commentaire' => $commentaire,
            'cree_par' => auth()->id(),
        ]);
    }
}
